<?php

namespace Pialsoft\LaravelPushManager;

use Illuminate\Support\ServiceProvider;
use Pialsoft\LaravelPushManager\Client\FcmHttpV1Client;
use Pialsoft\LaravelPushManager\Client\GoogleCredentialsProvider;
use RuntimeException;

class PushManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/push-manager.php',
            'push-manager'
        );

        $this->app->singleton(
            GoogleCredentialsProvider::class,
            function ($app): GoogleCredentialsProvider {
                return new GoogleCredentialsProvider(
                    $this->resolveCredentialsFile($app)
                );
            }
        );

        $this->app->singleton(
            FcmHttpV1Client::class,
            function ($app): FcmHttpV1Client {
                return new FcmHttpV1Client(
                    $this->resolveProjectId($app),
                    $app->make(GoogleCredentialsProvider::class),
                    null,
                    (int) $app['config']->get(
                        'push-manager.http.timeout',
                        10
                    )
                );
            }
        );
    }

    private function resolveCredentialsFile($app): string
    {
        $credentialsFile = $app['config']->get(
            'push-manager.credentials.file'
        );

        if (! is_string($credentialsFile) || trim($credentialsFile) === '') {
            throw new RuntimeException(
                'Set the Firebase credentials file in push-manager.credentials.file.'
            );
        }

        if (! $this->isAbsolutePath($credentialsFile)) {
            $credentialsFile = base_path($credentialsFile);
        }

        return $credentialsFile;
    }

    private function resolveProjectId($app): string
    {
        $projectId = $app['config']->get('push-manager.project_id');

        if (! is_string($projectId) || trim($projectId) === '') {
            throw new RuntimeException(
                'Set the Firebase project ID in push-manager.project_id.'
            );
        }

        return $projectId;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/push-manager.php' => config_path('push-manager.php'),
        ], 'push-manager-config');
    }
}
