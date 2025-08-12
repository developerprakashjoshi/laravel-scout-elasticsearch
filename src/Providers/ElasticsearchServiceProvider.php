<?php

namespace LaravelScout\Elasticsearch\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use LaravelScout\Elasticsearch\Services\ElasticsearchEngine;
use Elastic\Elasticsearch\Client;

class ElasticsearchServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Client::class, function ($app) {
            $config = config('scout.elasticsearch');
            
            $builder = \Elastic\Elasticsearch\ClientBuilder::create()
                ->setHosts($config['hosts']);
            
            // Add authentication if credentials are provided
            if (!empty($config['username']) && !empty($config['password'])) {
                $builder->setBasicAuthentication($config['username'], $config['password']);
            }
            
            // Handle SSL verification
            if (isset($config['ssl_verification']) && !$config['ssl_verification']) {
                $builder->setSSLVerification(false);
            }
            
            return $builder->build();
        });
    }

    public function boot()
    {
        // Publish configuration file
        $this->publishes([
            __DIR__ . '/../../config/scout.php' => config_path('scout.php'),
        ], 'laravel-scout-elasticsearch-config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \LaravelScout\Elasticsearch\Console\Commands\ScoutTestElasticsearch::class,
                \LaravelScout\Elasticsearch\Console\Commands\ZeroDowntimeReindex::class,
                \LaravelScout\Elasticsearch\Console\Commands\ScoutLazyBackfill::class,
                \LaravelScout\Elasticsearch\Console\Commands\AddNewFieldsReindex::class,
                \LaravelScout\Elasticsearch\Console\Commands\ReindexLargeDataset::class,
                \LaravelScout\Elasticsearch\Console\Commands\SeedLargeDataset::class,
            ]);
        }

        resolve(EngineManager::class)->extend('elasticsearch', function () {
            return new ElasticsearchEngine(
                app(Client::class)
            );
        });
    }
} 