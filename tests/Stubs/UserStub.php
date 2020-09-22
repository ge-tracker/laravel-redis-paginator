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
        ['id' => 'user:6', 'name' => 'Test user 6'],
        ['id' => 'user:7', 'name' => 'Test user 7'],
        ['id' => 'user:8', 'name' => 'Test user 8'],
        ['id' => 'user:9', 'name' => 'Test user 9'],
        ['id' => 'user:10', 'name' => 'Test user 10'],
        ['id' => 'user:11', 'name' => 'Test user 11'],
        ['id' => 'user:12', 'name' => 'Test user 12'],
        ['id' => 'user:13', 'name' => 'Test user 13'],
        ['id' => 'user:14', 'name' => 'Test user 14'],
        ['id' => 'user:15', 'name' => 'Test user 15'],
        ['id' => 'user:16', 'name' => 'Test user 16'],
        ['id' => 'user:17', 'name' => 'Test user 17'],
        ['id' => 'user:18', 'name' => 'Test user 18'],
        ['id' => 'user:19', 'name' => 'Test user 19'],
        ['id' => 'user:20', 'name' => 'Test user 20'],
        ['id' => 'user:21', 'name' => 'Test user 21'],
        ['id' => 'user:22', 'name' => 'Test user 22'],
        ['id' => 'user:23', 'name' => 'Test user 23'],
        ['id' => 'user:24', 'name' => 'Test user 24'],
        ['id' => 'user:25', 'name' => 'Test user 25'],
    ];

    public static function whereInTestArray(string $field, array $values)
    {
        return array_filter(static::$users, fn($user) => in_array($user[$field], $values, true));
    }
}
