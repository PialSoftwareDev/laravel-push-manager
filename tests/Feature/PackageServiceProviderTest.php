<?php

namespace Pialsoft\LaravelPushManager\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Pialsoft\LaravelPushManager\PushManagerServiceProvider;

class PackageServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PushManagerServiceProvider::class,
        ];
    }

    public function test_package_configuration_is_registered(): void
    {
        $this->assertSame(
            'push-notifications',
            config('push-manager.queue.name')
        );

        $this->assertSame(
            10,
            config('push-manager.http.timeout')
        );
    }

    public function test_package_configuration_can_be_published(): void
    {
        $this->artisan('vendor:publish', [
            '--tag' => 'push-manager-config',
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertFileExists(
            config_path('push-manager.php')
        );
    }

    public function test_service_provider_requires_project_id(): void
    {
        config()->set('push-manager.project_id', null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Set the Firebase project ID in push-manager.project_id.'
        );

        $this->app->make(\Pialsoft\LaravelPushManager\Client\FcmHttpV1Client::class);
    }

    public function test_service_provider_requires_credentials_file(): void
    {
        config()->set('push-manager.project_id', 'demo-project');
        config()->set('push-manager.credentials.file', null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Set the Firebase credentials file in push-manager.credentials.file.'
        );

        $this->app->make(\Pialsoft\LaravelPushManager\Client\GoogleCredentialsProvider::class);
    }
}
