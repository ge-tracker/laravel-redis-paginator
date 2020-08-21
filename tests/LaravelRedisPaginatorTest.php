<?php

namespace GeTracker\LaravelRedisPaginator\Tests;

use GeTracker\LaravelRedisPaginator\Exceptions\InvalidKeyException;
use GeTracker\LaravelRedisPaginator\LaravelRedisPaginator;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

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
    public function it_should_load_specified_page_with_func_args(): void
    {
        $results = $this->paginator->key('leaderboard')->paginate('page', 2);

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
    public function it_should_load_specified_page_with_func_args_and_per_page(): void
    {
        $results = $this->paginator->perPage(2)->key('leaderboard')->paginate('page', 8);

        self::assertSame(25, $results->total());
        self::assertCount(2, $results->items());

        // Ensure correct page returned
        self::assertSame('user:15', array_key_first($results->items()));
        self::assertSame('user:16', array_key_last($results->items()));
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

    /** @test */
    public function it_should_load_page_from_request(): void
    {
        Route::get('/test', static function () {
            return (new LaravelRedisPaginator())->key('leaderboard')->paginate();
        });

        // Default to page 1
        $this->get('/test')
            ->assertOk()
            ->assertJson([
                'current_page' => 1,
                'data'         => [
                    'user:1' => 1000,
                    'user:2' => 2000,
                ],
                'per_page'     => 15,
                'total'        => 25,
            ]);

        // Manually specify page 2
        $this->get('/test?page=2')
            ->assertOk()
            ->assertJson([
                'current_page' => 2,
                'data'         => [
                    'user:16' => 16000,
                    'user:17' => 17000,
                ],
                'per_page'     => 15,
                'total'        => 25,
            ]);
    }

    /** @test */
    public function it_should_find_member_rank(): void
    {
        $rank1 = $this->paginator->key('leaderboard')->rank('user:7');
        $rank2 = $this->paginator->key('leaderboard')->rank('user:17');
        $rank3 = $this->paginator->key('leaderboard')->perPage(2)->rank('user:17');
        $rank4 = $this->paginator->key('leaderboard')->perPage(2)->rank('user:2');
        $rank5 = $this->paginator->key('leaderboard')->perPage(2)->rank('user:25');
        $invalid = $this->paginator->key('leaderboard')->rank('invalid-user');

        self::assertSame(1, $rank1->page);
        self::assertSame(6, $rank1->rank);
        self::assertSame(7000.0, $rank1->score);

        self::assertSame(2, $rank2->page);
        self::assertSame(16, $rank2->rank);
        self::assertSame(17000.0, $rank2->score);

        self::assertSame(9, $rank3->page);
        self::assertSame(1, $rank4->page);
        self::assertSame(13, $rank5->page);

        self::assertNull($invalid);
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
