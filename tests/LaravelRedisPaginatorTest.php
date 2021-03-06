<?php

namespace GeTracker\LaravelRedisPaginator\Tests;

use GeTracker\LaravelRedisPaginator\Exceptions\InvalidKeyException;
use GeTracker\LaravelRedisPaginator\LaravelRedisPaginator;
use GeTracker\LaravelRedisPaginator\Tests\Stubs\ArrayResolverStub;
use GeTracker\LaravelRedisPaginator\Tests\Stubs\EloquentResolverStub;
use GeTracker\LaravelRedisPaginator\Tests\Stubs\UserStub;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

class LaravelRedisPaginatorTest extends TestCase
{
    use RefreshDatabase;

    private LaravelRedisPaginator $paginator;

    public function setUp(): void
    {
        parent::setUp();

        $this->paginator = $this->app->make(LaravelRedisPaginator::class);
        $this->fakeData();
    }

    /** @test */
    public function it_should_load_default_per_page(): void
    {
        $results = $this->paginator->paginate('leaderboard');

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
        $results = $this->paginator->page(2)->paginate('leaderboard');

        self::assertSame(25, $results->total());
        self::assertCount(10, $results->items());

        // Ensure correct page returned
        self::assertSame('user:16', array_key_first($results->items()));
        self::assertSame('user:25', array_key_last($results->items()));
    }

    /** @test */
    public function it_should_load_specified_page_with_func_args(): void
    {
        $results = $this->paginator->key('leaderboard')->paginate('leaderboard', 'page', 2);

        self::assertSame(25, $results->total());
        self::assertCount(10, $results->items());

        // Ensure correct page returned
        self::assertSame('user:16', array_key_first($results->items()));
        self::assertSame('user:25', array_key_last($results->items()));
    }

    /** @test */
    public function it_should_set_per_page(): void
    {
        $results = $this->paginator->perPage(2)->paginate('leaderboard');

        self::assertSame(13, $results->lastPage());
        self::assertCount(2, $results->items());
    }

    /** @test */
    public function it_should_load_specified_page_with_func_args_and_per_page(): void
    {
        $results = $this->paginator->perPage(2)->key('leaderboard')->paginate('leaderboard', 'page', 8);

        self::assertSame(25, $results->total());
        self::assertCount(2, $results->items());

        // Ensure correct page returned
        self::assertSame('user:15', array_key_first($results->items()));
        self::assertSame('user:16', array_key_last($results->items()));
    }

    /** @test */
    public function it_should_sort_asc(): void
    {
        $results = $this->paginator->sortAsc()->paginate('leaderboard');

        self::assertSame('user:1', array_key_first($results->items()));
    }

    /** @test */
    public function it_should_sort_desc(): void
    {
        $results = $this->paginator->sortDesc()->paginate('leaderboard');

        self::assertSame('user:25', array_key_first($results->items()));
    }

    /** @test */
    public function it_should_load_page_from_request(): void
    {
        Route::get('/test', static function () {
            return (new LaravelRedisPaginator())->paginate('leaderboard');
        });

        // Default to page 1
        $this->get('/test')
            ->assertOk()
            ->assertJson([
                'current_page' => 1,
                'data' => [
                    'user:1' => 1000,
                    'user:2' => 2000,
                ],
                'per_page' => 15,
                'total' => 25,
            ]);

        // Manually specify page 2
        $this->get('/test?page=2')
            ->assertOk()
            ->assertJson([
                'current_page' => 2,
                'data' => [
                    'user:16' => 16000,
                    'user:17' => 17000,
                ],
                'per_page' => 15,
                'total' => 25,
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
        $rank6 = $this->paginator->rank('user:7', 'leaderboard');
        $invalid = $this->paginator->key('leaderboard')->rank('invalid-user');
        $invalidKey = $this->paginator->key('invalid-key')->rank('invalid-user');

        self::assertSame(1, $rank1->page);
        self::assertSame(6, $rank1->rank);
        self::assertSame(7000.0, $rank1->score);

        self::assertSame(2, $rank2->page);
        self::assertSame(16, $rank2->rank);
        self::assertSame(17000.0, $rank2->score);

        self::assertSame(9, $rank3->page);
        self::assertSame(1, $rank4->page);
        self::assertSame(13, $rank5->page);
        self::assertSame(7000.0, $rank6->score);

        self::assertNull($invalid);
        self::assertNull($invalidKey);
    }

    /** @test */
    public function it_should_find_member_rank_desc(): void
    {
        $rank1 = $this->paginator->sortDesc()->rank('user:7', 'leaderboard');
        $rank2 = $this->paginator->sortDesc()->rank('user:17', 'leaderboard');
        $rank3 = $this->paginator->sortDesc()->rank('user:23', 'leaderboard');
        $rank4 = $this->paginator->sortDesc()->rank('user:2', 'leaderboard');

        self::assertSame(2, $rank1->page);
        self::assertSame(18, $rank1->rank);
        self::assertSame(7000.0, $rank1->score);

        self::assertSame(1, $rank2->page);
        self::assertSame(8, $rank2->rank);
        self::assertSame(17000.0, $rank2->score);

        self::assertSame(1, $rank3->page);
        self::assertSame(2, $rank4->page);
    }

    /** @test */
    public function it_should_throw_if_no_rank_key(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->paginator->rank('asd');
    }

    /** @test */
    public function it_should_resolve_eloquent_users_using_static_resolver(): void
    {
        $users = factory(UserStub::class, 5)->create();

        $this->paginator->setModelResolver(new EloquentResolverStub());

        $results = $this->paginator->paginate('leaderboard');

        self::assertCount(5, $results->items());
        self::assertInstanceOf(UserStub::class, $results[0]);
        self::assertSame($users[0]->name, $results[0]->name);
        self::assertSame(1000, (int)$results[0]->score);
        self::assertSame($users[4]->name, $results[4]->name);
        self::assertSame(5000, (int)$results[4]->score);
    }

    /** @test */
    public function it_should_resolve_array_users_using_static_resolver(): void
    {
        $users = factory(UserStub::class, 5)->create();

        $this->paginator->setModelResolver(new ArrayResolverStub());

        $results = $this->paginator->paginate('leaderboard');

        self::assertCount(14, $results->items());
        self::assertSame('Test user 1', $results[0]['name']);
        self::assertSame(1000, (int)$results[0]['score']);
        self::assertSame('Test user 5', $results[3]['name']);
        self::assertSame(5000, (int)$results[3]['score']);
    }

    /** @test */
    public function it_should_resolve_eloquent_users_using_static_resolver_desc(): void
    {
        $users = factory(UserStub::class, 25)->create();

        $results = $this->paginator
            ->setModelResolver(new EloquentResolverStub())
            ->sortDesc()
            ->paginate('leaderboard');

        self::assertCount(15, $results->items());

        // Assert that the results are still in descending order, as sorted by Redis
        self::assertSame($users[24]->name, $results[0]->name);
        self::assertSame(25000, (int)$results[0]->score);
        self::assertSame($users[15]->name, $results[9]->name);
        self::assertSame(16000, (int)$results[9]->score);
    }

    /** @test */
    public function it_should_resolve_array_users_using_static_resolver_desc(): void
    {
        $results = $this->paginator
            ->setModelResolver(new ArrayResolverStub())
            ->sortDesc()
            ->paginate('leaderboard');

        self::assertCount(15, $results->items());

        // Assert that the results are still in descending order, as sorted by Redis
        self::assertSame(25000, (int)$results[0]['score']);
        self::assertSame(16000, (int)$results[9]['score']);
    }

    /** @test */
    public function it_should_resolve_eloquent_with_missing_id(): void
    {
        $users = factory(UserStub::class, 10)->create();

        // delete a user
        UserStub::where('id', 3)->delete();

        $results = $this->paginator
            ->setModelResolver(new EloquentResolverStub())
            ->sortAsc()
            ->paginate('leaderboard');

        self::assertCount(9, $results->items());
    }

    /** @test */
    public function it_should_resolve_array_with_missing_id(): void
    {
        $results = $this->paginator
            ->setModelResolver(new ArrayResolverStub())
            ->sortAsc()
            ->paginate('leaderboard');

        self::assertCount(14, $results->items());
    }

    /**
     * Generate fake users and scores.
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
