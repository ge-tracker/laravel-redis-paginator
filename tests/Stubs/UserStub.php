<?php

namespace GeTracker\LaravelRedisPaginator\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class UserStub extends Model
{
    protected $table = 'users';

    private static array $users = [
        ['id' => 'user:1', 'name' => 'Test user 1'],
        ['id' => 'user:2', 'name' => 'Test user 2'],
        //['id' => 'user:3', 'name' => 'Test user 3'],
        ['id' => 'user:4', 'name' => 'Test user 4'],
        ['id' => 'user:5', 'name' => 'Test user 5'],
    ];

    public static function whereInTestArray(string $field, array $values)
    {
        return array_filter(static::$users, fn ($user) => in_array($user[$field], $values, true));
    }
}
