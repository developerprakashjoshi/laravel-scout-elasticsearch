<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use App\Services\ElasticsearchEngine;
use Elastic\Elasticsearch\Client;

class ElasticsearchServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Client::class, function ($app) {
            $config = config('services.elasticsearch');
            
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
        resolve(EngineManager::class)->extend('elasticsearch', function () {
            return new ElasticsearchEngine(
                app(Client::class)
            );
        });
    }
} 