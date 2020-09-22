<?php

use Faker\Generator;
use GeTracker\LaravelRedisPaginator\Tests\Stubs\UserStub;

/* @var Illuminate\Database\Eloquent\Factory $factory */
$factory->define(UserStub::class, function (Generator $faker) {
    return [
        'name' => $faker->name,
        'uuid' => $faker->uuid,
    ];
});
