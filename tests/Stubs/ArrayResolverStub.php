<?php

namespace GeTracker\LaravelRedisPaginator\Tests\Stubs;

use GeTracker\LaravelRedisPaginator\Resolvers\AbstractResolver;

class ArrayResolverStub extends AbstractResolver
{
    /**
     * {@inheritdoc}
     */
    protected function resolveModels(array $keys)
    {
        return UserStub::whereInTestArray('id', $keys);
    }

    /**
     * {@inheritdoc}
     */
    protected function resolveKey($key)
    {
        return $key;
    }
}
