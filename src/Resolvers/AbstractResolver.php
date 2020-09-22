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
     * Field to be merged into the collection of models containing the Redis result.
     *
     * @var string
     */
    protected $scoreField = 'score';

    /**
     * Redis results.
     *
     * @var Collection
     */
    protected Collection $results;

    /**
     * Model Key -> Member mapping.
     *
     * @var array
     */
    protected array $resolvedKeyMembers;

    /**
     * Member -> Model Key mapping.
     *
     * @var array
     */
    protected array $resolvedMemberKeys;

    /**
     * Resolve an array of Redis results to their respective models.
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
            return $this->mapModels($models, true);
        }

        return new Collection($this->mapModels($models, false));
    }

    /**
     * Map scores to eloquent models.
     *
     * @param Collection|Model[] $models
     * @param bool               $eloquent
     *
     * @return Collection|Model[]
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress MismatchingDocblockParamType
     */
    private function mapModels($models, bool $eloquent)
    {
        // Key the models by the defined key
        $models = $this->keyModels($models);

        $collection = $this->results->map(function ($score, $redisKey) use ($models, $eloquent) {
            $eloquentKey = $this->getEloquentKey($redisKey);

            if (!$eloquentKey || !$model = $models->get($eloquentKey)) {
                return null;
            }

            // Set the defined score property on the model
            if ($eloquent) {
                $model->setRelation($this->scoreField, $score);
            } else {
                $model += [$this->scoreField => $score];
            }

            return $model;
        })->filter()->values();

        return $eloquent ? $collection : $collection->toArray();
    }

    /**
     * Key collections by the defined model key.
     *
     * @param $array
     *
     * @return Collection
     */
    private function keyModels($array): Collection
    {
        $models = new Collection($array);

        // Key the models by the defined key
        $models = $models->keyBy($this->modelKey);

        return $models;
    }

    /**
     * Get an already resolved Redis key.
     *
     * @param $model
     *
     * @return string|int|null
     */
    private function getRedisKey($model)
    {
        if ($model instanceof Model) {
            return $this->resolvedKeyMembers[$model->getAttribute($this->modelKey)] ?? null;
        }

        return $this->resolvedKeyMembers[$model[$this->modelKey]] ?? null;
    }

    /**
     * Get an already resolved Eloquent key.
     *
     * @param string $key
     *
     * @return string|int|null
     */
    private function getEloquentKey(string $key)
    {
        return $this->resolvedMemberKeys[$key] ?? null;
    }

    /**
     * Map keys using the key resolver.
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
                $this->resolvedKeyMembers[$resolved] = $key;
                $this->resolvedMemberKeys[$key] = $resolved;

                return $resolved;
            })->toArray();
    }

    /**
     * Load Eloquent models.
     *
     * @param array $keys
     *
     * @return Model[]|Collection
     */
    abstract protected function resolveModels(array $keys);

    /**
     * Resolve a key from Redis to an Eloquent incrementing ID or UUID.
     *
     * @param string $key
     *
     * @return string|int
     */
    abstract protected function resolveKey($key);
}
