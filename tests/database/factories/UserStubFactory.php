<?php

namespace GeTracker\LaravelRedisPaginator\Tests\Database\Factories;

use GeTracker\LaravelRedisPaginator\Tests\Stubs\UserStub;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserStubFactory extends Factory
{
    protected $model = UserStub::class;

    /**
     * @inheritDoc
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'uuid' => $this->faker->uuid,
        ];
    }
}
