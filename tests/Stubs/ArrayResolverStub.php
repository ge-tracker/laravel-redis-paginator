<?php

namespace GeTracker\LaravelRedisPaginator\Tests\Stubs;

use GeTracker\LaravelRedisPaginator\Resolvers\AbstractResolver;

class ArrayResolverStub extends AbstractResolver
{
    /**
     * @inheritDoc
     */
    protected function resolveModels(array $keys)
    {
        return UserStub::whereInTestArray('id', $keys);
    }

    /**
     * @inheritDoc
     */
    protected function resolveKey($key)
    {
        return $key;
    }
}
