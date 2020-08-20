<?php

namespace GeTracker\LaravelRedisPaginator\Tests;

use GeTracker\LaravelRedisPaginator\Exceptions\InvalidKeyException;
use GeTracker\LaravelRedisPaginator\LaravelRedisPaginator;
use Illuminate\Support\Facades\Redis;

class LaravelRedisPaginatorTest extends TestCase
{
    private LaravelRedisPaginator $paginator;

    public function setUp(): void
    {
        parent::setUp();

        $this->paginator = $this->app->make(LaravelRedisPaginator::class);
        $this->fakeData();
    }

    /** @test */
    public function it_should_throw_exception_for_no_key(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->paginator->paginate();
    }

    /** @test */
    public function it_should_load_default_per_page(): void
    {
        $results = $this->paginator->key('leaderboard')->paginate();

        self::assertSame(25, $results->total());
        self::assertSame(15, $results->perPage());
        self::assertCount(15, $results->items());

        // Ensure correct page returned
        self::assertSame('user:1', array_key_first($results->items()));
        self::assertSame('user:15', array_key_last($results->items()));
    }

    /** @test */
    public function it_should_load_specified_page(): void
    {
        $results = $this->paginator->page(2)->key('leaderboard')->paginate();

        self::assertSame(25, $results->total());
        self::assertCount(10, $results->items());

        // Ensure correct page returned
        self::assertSame('user:16', array_key_first($results->items()));
        self::assertSame('user:25', array_key_last($results->items()));
    }

    /** @test */
    public function it_should_set_per_page(): void
    {
        $results = $this->paginator->perPage(2)->key('leaderboard')->paginate();

        self::assertSame(13, $results->lastPage());
        self::assertCount(2, $results->items());
    }

    /** @test */
    public function it_should_sort_asc(): void
    {
        $results = $this->paginator->sortAsc()->key('leaderboard')->paginate();

        self::assertSame('user:1', array_key_first($results->items()));
    }

    /** @test */
    public function it_should_sort_desc(): void
    {
        $results = $this->paginator->sortDesc()->key('leaderboard')->paginate();

        self::assertSame('user:25', array_key_first($results->items()));
    }

    /**
     * Generate fake users and scores
     *
     * @param int $n
     */
    private function fakeData($n = 25): void
    {
        Redis::del('leaderboard');

        for ($i = $n; $i > 0; $i--) {
            Redis::zAdd('leaderboard', $i * 1000, 'user:' . $i);
        }
    }
}
