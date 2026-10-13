<?php

namespace Pialsoft\LaravelPushManager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pialsoft\LaravelPushManager\Client\GoogleCredentialsProvider;
use RuntimeException;

class GoogleCredentialsProviderTest extends TestCase
{
    public function test_missing_credentials_file_throws_exception(): void
    {
        $path = sys_get_temp_dir().'/missing-'.uniqid().'.json';
        $provider = new GoogleCredentialsProvider($path);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Firebase service-account credentials file was not found.'
        );

        $provider->getAccessToken();
    }

    public function test_invalid_json_throws_exception(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'fcm-');
        file_put_contents($path, '{invalid-json');

        try {
            $provider = new GoogleCredentialsProvider($path);

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage(
                'Firebase service-account credentials contain invalid JSON.'
            );

            $provider->getAccessToken();
        } finally {
            @unlink($path);
        }
    }

    public function test_missing_service_account_fields_throw_exception(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'fcm-');
        file_put_contents($path, json_encode([
            'type' => 'service_account',
        ]));

        try {
            $provider = new GoogleCredentialsProvider($path);

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage(
                'Firebase service-account credentials are missing required fields.'
            );

            $provider->getAccessToken();
        } finally {
            @unlink($path);
        }
    }
}
