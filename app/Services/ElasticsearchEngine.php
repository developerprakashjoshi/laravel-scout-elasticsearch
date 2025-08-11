<?php

namespace App\Services;

use Laravel\Scout\Engines\Engine;
use Laravel\Scout\Builder;
use Illuminate\Database\Eloquent\Collection;
use Elastic\Elasticsearch\Client;
use Illuminate\Support\LazyCollection;

class ElasticsearchEngine extends Engine
{
    protected $elasticsearch;
    protected $index;

    public function __construct(Client $elasticsearch, $index = null)
    {
        $this->elasticsearch = $elasticsearch;
        $this->index = $index;
    }

    public function update($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $params['body'] = [];

        $models->each(function ($model) use (&$params) {
            $params['body'][] = [
                'index' => [
                    '_index' => $this->index ?: $model->searchableAs(),
                    '_id' => $model->getKey(),
                ]
            ];

            $params['body'][] = $model->toSearchableArray();
        });

        try {
            $this->elasticsearch->bulk($params);
        } catch (\Exception $e) {
            // Log the error but don't throw it to prevent breaking the application
            \Log::error('Elasticsearch update error: ' . $e->getMessage());
        }
    }

    public function delete($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $params['body'] = [];

        $models->each(function ($model) use (&$params) {
            $params['body'][] = [
                'delete' => [
                    '_index' => $this->index ?: $model->searchableAs(),
                    '_id' => $model->getKey(),
                ]
            ];
        });

        try {
            $this->elasticsearch->bulk($params);
        } catch (\Exception $e) {
            // Log the error but don't throw it to prevent breaking the application
            \Log::error('Elasticsearch delete error: ' . $e->getMessage());
        }
    }

    public function search(Builder $builder)
    {
        return $this->performSearch($builder, array_filter([
            'numericFilters' => $this->filters($builder),
            'size' => $builder->limit,
        ]));
    }

    public function paginate(Builder $builder, $perPage, $page)
    {
        $result = $this->performSearch($builder, [
            'numericFilters' => $this->filters($builder),
            'from' => ($page - 1) * $perPage,
            'size' => $perPage,
        ]);

        $totalHits = $result['hits']['total']['value'] ?? 0;
        
        return [
            'hits' => $result['hits'],
            'nbHits' => $totalHits,
            'nbPages' => ceil($totalHits / $perPage),
        ];
    }

    public function mapIds($results)
    {
        return collect($results['hits']['hits'])->pluck('_id')->values();
    }

    public function map(Builder $builder, $results, $model)
    {
        if (count($results['hits']['hits']) === 0) {
            return Collection::make();
        }

        $keys = collect($results['hits']['hits'])
            ->pluck('_id')
            ->values()
            ->all();

        $models = $model->whereIn(
            $model->getKeyName(),
            $keys
        )->get()->keyBy($model->getKeyName());

        return collect($results['hits']['hits'])->map(function ($hit) use ($models) {
            $id = $hit['_id'];

            if (isset($models[$id])) {
                return $models[$id];
            }
        })->filter();
    }

    public function lazyMap(Builder $builder, $results, $model)
    {
        if (count($results['hits']['hits']) === 0) {
            return LazyCollection::make();
        }

        $keys = collect($results['hits']['hits'])
            ->pluck('_id')
            ->values()
            ->all();

        $models = $model->whereIn(
            $model->getKeyName(),
            $keys
        )->get()->keyBy($model->getKeyName());

        return LazyCollection::make($results['hits']['hits'])->map(function ($hit) use ($models) {
            $id = $hit['_id'];

            if (isset($models[$id])) {
                return $models[$id];
            }
        })->filter();
    }

    public function getTotalCount($results)
    {
        return $results['hits']['total']['value'] ?? 0;
    }

    public function flush($model)
    {
        $index = $this->index ?: $model->searchableAs();

        $this->elasticsearch->indices()->delete([
            'index' => $index
        ]);

        // Create index with custom mapping if available
        $this->createIndex($index, ['model' => $model]);
    }

    public function createIndex($name, array $options = [])
    {
        $mapping = [];
        
        // Check if model has custom mapping
        if (isset($options['model']) && method_exists($options['model'], 'getSearchableMapping')) {
            $mapping = $options['model']->getSearchableMapping();
        }
        
        $params = [
            'index' => $name
        ];
        
        // Add mapping if provided
        if (!empty($mapping)) {
            $params['body'] = [
                'mappings' => $mapping
            ];
        }
        
        $this->elasticsearch->indices()->create($params);
    }

    public function deleteIndex($name)
    {
        $this->elasticsearch->indices()->delete([
            'index' => $name
        ]);
    }

    protected function performSearch(Builder $builder, array $options = [])
    {
        // Build the base query
        $searchQuery = [
            'multi_match' => [
                'query' => $builder->query,
                'fields' => ['*'],
                'type' => 'best_fields',
                'fuzziness' => 'AUTO'
            ]
        ];

        // If filters is present, wrap in bool query
        if (isset($options['numericFilters']) && count($options['numericFilters'])) {
            $searchQuery = [
                'bool' => [
                    'must' => [
                        'multi_match' => [
                            'query' => $builder->query,
                            'fields' => ['*'],
                            'type' => 'best_fields',
                            'fuzziness' => 'AUTO'
                        ]
                    ],
                    'filter' => $options['numericFilters']
                ]
            ];
        }

        $query = [
            'index' => $this->index ?: $builder->model->searchableAs(),
            'body' => [
                'query' => $searchQuery,
                'size' => $options['size'] ?? 10,
            ]
        ];

        if (isset($options['from'])) {
            $query['body']['from'] = $options['from'];
        }

        return $this->elasticsearch->search($query);
    }

    protected function filters(Builder $builder)
    {
        $filters = [];
        
        foreach ($builder->wheres as $key => $value) {
            // Handle range queries (>=, <=, >, <)
            if (is_array($value) && isset($value['operator']) && isset($value['value'])) {
                $operator = $value['operator'];
                $val = $value['value'];
                
                if (in_array($operator, ['>=', '>', '<=', '<'])) {
                    $rangeOperator = $operator === '>=' ? 'gte' : ($operator === '>' ? 'gt' : ($operator === '<=' ? 'lte' : 'lt'));
                    $filters[] = ['range' => [$key => [$rangeOperator => $val]]];
                } else {
                    // Fallback to term query
                    $filters[] = ['term' => [$key => $val]];
                }
            } else {
                // The standard Elasticsearch pattern for exact matches
                $filters[] = ['term' => [$key . '.keyword' => $value]];
            }
        }
        
        return $filters;
    }
} 