<?php

namespace GeTracker\LaravelRedisPaginator;

use GeTracker\LaravelRedisPaginator\Exceptions\InvalidKeyException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;

class LaravelRedisPaginator
{
    protected int $perPage = 15;
    protected int $currentPage = 1;
    protected ?string $key;
    protected bool $asc = true;

    public function paginate($pageName = 'page', ?int $page = null): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $this->validateArguments();

        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $total = $this->loadTotalItems();

        return new LengthAwarePaginator(
            $this->results($total),
            $total,
            $this->perPage
        );
    }

    /**
     * Load paginated results from Redis
     *
     * @param int $total
     *
     * @return Collection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function results(int $total)
    {
        // Calculate start and end positions
        $start = ($this->currentPage - 1) * $this->perPage;
        $end = ($this->currentPage * $this->perPage) - 1;

        return $this->loadFromRedis($start, $end);
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

        return $this->newCollection($results);
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
     * @param array $items
     *
     * @return Collection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function newCollection(array $items)
    {
        return new Collection($items);
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
}
