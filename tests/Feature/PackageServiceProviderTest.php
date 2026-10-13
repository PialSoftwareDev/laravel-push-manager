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
}
