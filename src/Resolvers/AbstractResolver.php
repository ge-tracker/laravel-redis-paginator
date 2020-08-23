<?php

namespace GeTracker\LaravelRedisPaginator\Resolvers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class AbstractResolver
{
    /**
     * The key that maps models to their Redis counterpart.
     *
     * @var string
     */
    protected $modelKey = 'id';

    /**
     * Field to be merged into the collection of models containing the Redis result
     *
     * @var string
     */
    protected $scoreField = 'score';

    /**
     * Redis results
     *
     * @var Collection
     */
    protected Collection $results;

    /**
     * Reserve key mapping
     *
     * @var array
     */
    protected array $resolvedKeys;

    /**
     * Resolve an array of Redis results to their respective models
     *
     * @param Collection $results
     *
     * @return Collection
     * @psalm-suppress InvalidReturnType
     */
    public function resolve(Collection $results): Collection
    {
        $this->results = $results;

        $keys = $this->mapKeys();
        $models = $this->resolveModels($keys);

        if (!count($results)) {
            return new Collection();
        }

        if ($models instanceof Collection && $models[0] instanceof Model) {
            /** @psalm-suppress InvalidReturnStatement */
            return $this->mapEloquent($models);
        }

        return new Collection($this->mapArray($models));
    }

    /**
     * Map scores to eloquent models
     *
     * @param Collection|Model[] $models
     *
     * @return Collection|Model[]
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress MismatchingDocblockParamType
     */
    private function mapEloquent(Collection $models)
    {
        foreach ($models as $model) {
            if (!$redisKey = $this->getRedisKey($model)) {
                continue;
            }

            // Set the defined score property on the model
            $model->setRelation($this->scoreField, $this->results[$redisKey]);
        }

        return $models;
    }

    /**
     * Map scores to eloquent models
     *
     * @param array $models
     *
     * @return array
     */
    private function mapArray(array $models): array
    {
        foreach ($models as &$model) {
            if (!$redisKey = $this->getRedisKey($model)) {
                continue;
            }

            // Set the defined score property on the model
            $model += [$this->scoreField => $this->results[$redisKey]];
        }

        return $models;
    }

    /**
     * Get an already resolved Redis key
     *
     * @param $model
     *
     * @return string|int
     */
    private function getRedisKey($model)
    {
        return $model instanceof Model
            ? $this->resolvedKeys[$model->getAttribute($this->modelKey)]
            : $this->resolvedKeys[$model[$this->modelKey]];
    }

    /**
     * Map keys using the key resolver
     *
     * @return array
     */
    private function mapKeys(): array
    {
        return $this->results
            ->keys()
            ->map(function ($key) {
                // Resolve the key
                $resolved = $this->resolveKey($key);

                // Cache the resolved key to map scores to the models
                $this->resolvedKeys[$resolved] = $key;

                return $resolved;
            })->toArray();
    }

    /**
     * Load Eloquent models
     *
     * @param array $keys
     *
     * @return Model[]|Collection
     */
    abstract protected function resolveModels(array $keys);

    /**
     * Resolve a key from Redis to an Eloquent incrementing ID or UUID
     *
     * @param string $key
     *
     * @return string|int
     */
    abstract protected function resolveKey($key);
}
