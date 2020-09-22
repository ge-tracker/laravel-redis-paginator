<?php

namespace GeTracker\LaravelRedisPaginator\Tests\Stubs;

use GeTracker\LaravelRedisPaginator\Resolvers\AbstractResolver;

class EloquentResolverStub extends AbstractResolver
{
    protected $modelKey = 'id';
    protected $scoreField = 'score';

    /**
     * {@inheritdoc}
     */
    protected function resolveModels(array $keys)
    {
        return UserStub::whereIn('id', $keys)->get();
    }

    /**
     * {@inheritdoc}
     */
    protected function resolveKey($key)
    {
        return (int)str_replace('user:', '', $key);
    }
}
