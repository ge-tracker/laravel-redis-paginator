<?php

namespace GeTracker\LaravelRedisPaginator;

use GeTracker\LaravelRedisPaginator\Exceptions\InvalidKeyException;
use GeTracker\LaravelRedisPaginator\Resolvers\AbstractResolver;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;

class LaravelRedisPaginator
{
    protected int $perPage = 15;
    protected ?int $currentPage;
    protected ?string $key;
    protected bool $asc = true;

    protected ?AbstractResolver $modelResolver;

    /**
     * Load a Redis sorted set into a length aware paginator
     *
     * @see https://redis.io/topics/data-types-intro#redis-sorted-sets
     * @see https://laravel.com/docs/7.x/pagination#manually-creating-a-paginator
     *
     * @param string   $key
     * @param string   $pageName
     * @param int|null $page
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(string $key, string $pageName = 'page', ?int $page = null): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $this->key($key);
        $this->validateArguments();

        $page = $page ?? $this->resolveCurrentPage($pageName);

        $total = $this->loadTotalItems();

        return new LengthAwarePaginator(
            $this->results($page),
            $total,
            $this->perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]
        );
    }

    /**
     * Load paginated results from Redis
     *
     * @param int $page
     *
     * @return Collection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function results(int $page)
    {
        // Calculate start and end positions
        $start = ($page - 1) * $this->perPage;
        $end = ($page * $this->perPage) - 1;

        return $this->loadFromRedis($start, $end);
    }

    /**
     * Resolve current page from request or user-called method
     *
     * @param string $pageName
     *
     * @return int
     */
    private function resolveCurrentPage(string $pageName): int
    {
        return $this->currentPage ?? Paginator::resolveCurrentPage($pageName);
    }

    /**
     * Load results from Redis as a Collection
     *
     * @param int $start
     * @param int $end
     *
     * @return Collection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function loadFromRedis(int $start, int $end)
    {
        $results = $this->asc
            ? Redis::zRange($this->key, $start, $end, true)
            : Redis::zRevRange($this->key, $start, $end, true);

        $collection = $this->newCollection($results);

        // Return the raw results if not resolver was defined
        if (!isset($this->modelResolver)) {
            return $collection;
        }

        // Resolve the results to a collection of models
        return $this->modelResolver->resolve($collection);
    }

    /**
     * Get count of items in a sorted set
     *
     * @return int
     */
    protected function loadTotalItems(): int
    {
        return Redis::zCard($this->key);
    }

    /**
     * Create a new Collection
     *
     * @param array|Collection $items
     *
     * @return Collection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function newCollection($items)
    {
        return new Collection($items);
    }

    /**
     * Find the rank, page and score for a given member
     *
     * @param string      $member
     *
     * @param string|null $key
     *
     * @return MemberRank|null
     */
    public function rank(string $member, ?string $key = null): ?MemberRank
    {
        if ($key) {
            $this->key($key);
        }

        $this->validateArguments();

        // Run Lua script on Redis server to find the member's rank
        [$rank, $score] = $this->queryRank($member);

        if ($rank === false || $score === false) {
            return null;
        }

        $page = floor($rank / $this->perPage) + 1;

        return tap(new MemberRank(), static function (MemberRank $memberRank) use ($rank, $score, $page) {
            $memberRank->page = (int)$page;
            $memberRank->rank = (int)$rank;
            $memberRank->score = $score;
        });
    }

    /**
     * Run Lua script on Redis server to find the member's rank
     *
     * @param string $member
     *
     * @return array
     */
    private function queryRank(string $member): array
    {
        // Define the Lua script
        $lua = <<<LUA
return {
    redis.call(ARGV[1], KEYS[1], ARGV[2]),
    redis.call('ZSCORE', KEYS[1], ARGV[2])
}
LUA;

        // Build the script arguments
        $args = [
            $this->key,
            $this->asc ? 'ZRANK' : 'ZREVRANK',
            $member,
        ];

        return Redis::eval($lua, 1, ...$args);
    }

    private function validateArguments(): void
    {
        if (!isset($this->key)) {
            throw new InvalidKeyException();
        }
    }

    /**
     * @param int $perPage
     *
     * @return LaravelRedisPaginator
     */
    public function perPage(int $perPage): LaravelRedisPaginator
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * @param string $key
     *
     * @return LaravelRedisPaginator
     */
    public function key(string $key): LaravelRedisPaginator
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @param int $currentPage
     *
     * @return LaravelRedisPaginator
     */
    public function page(int $currentPage): LaravelRedisPaginator
    {
        $this->currentPage = $currentPage;

        return $this;
    }

    /**
     * @return LaravelRedisPaginator
     */
    public function sortAsc(): LaravelRedisPaginator
    {
        $this->asc = true;

        return $this;
    }

    /**
     * @return LaravelRedisPaginator
     */
    public function sortDesc(): LaravelRedisPaginator
    {
        $this->asc = false;

        return $this;
    }

    /**
     * @param AbstractResolver $modelResolver
     *
     * @return LaravelRedisPaginator
     */
    public function setModelResolver(AbstractResolver $modelResolver): LaravelRedisPaginator
    {
        $this->modelResolver = $modelResolver;

        return $this;
    }
}
