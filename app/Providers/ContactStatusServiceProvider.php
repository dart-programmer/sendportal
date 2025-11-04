<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Contacts\ContactStatusUpdaterInterface;
use App\Repositories\MongoContactRepository;
use App\Services\Contacts\GrpcContactClient;
use App\Services\Contacts\GrpcContactStatusUpdater;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use MongoDB\Client;
use Throwable;

class ContactStatusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GrpcContactClient::class, function ($app) {
            $config = $app['config']->get('services.contact_status', []);

            return new GrpcContactClient(
                $config['grpc_endpoint'] ?? null,
                (float) ($config['grpc_timeout'] ?? 2.0)
            );
        });

        $this->app->singleton(ContactStatusUpdaterInterface::class, function ($app) {
            return new GrpcContactStatusUpdater($app->make(GrpcContactClient::class));
        });

        $this->app->singleton(MongoContactRepository::class, function ($app) {
            $config = $app['config']->get('services.mongo', []);

            $uri = $config['uri'] ?? null;
            $database = $config['database'] ?? null;
            $collection = $config['contacts_collection'] ?? null;

            if (! $uri || ! $database || ! $collection || ! class_exists(Client::class)) {
                return new MongoContactRepository();
            }

            try {
                $client = new Client($uri);

                return new MongoContactRepository($client->selectCollection($database, $collection));
            } catch (Throwable $exception) {
                Log::warning('Unable to initialize Mongo contact repository; continuing without MongoDB.', [
                    'exception' => $exception->getMessage(),
                ]);

                return new MongoContactRepository();
            }
        });
    }
}
