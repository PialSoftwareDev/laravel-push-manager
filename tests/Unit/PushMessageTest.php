<?php

namespace Pialsoft\LaravelPushManager\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pialsoft\LaravelPushManager\Client\FcmHttpV1Client;
use Pialsoft\LaravelPushManager\Client\GoogleCredentialsProvider;

class PushMessageTest extends TestCase
{
    public function test_message_payload_is_built_correctly(): void
    {
        $history = [];

        $mock = new MockHandler([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"name":"projects/test-project/messages/test123"}'
            ),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $credentials = new class('unused.json') extends GoogleCredentialsProvider
        {
            public function getAccessToken(): string
            {
                return 'test-access-token';
            }
        };

        $client = new FcmHttpV1Client(
            'test-project',
            $credentials,
            new Client(['handler' => $stack])
        );

        $client->toToken('fake-device-token')
            ->title('Test title')
            ->body('Test body')
            ->data([
                'type' => 'announcement',
                'active' => true,
                'count' => 5,
            ])
            ->send();

        $requestBody = json_decode(
            (string) $history[0]['request']->getBody(),
            true
        );

        $message = $requestBody['message'];

        $this->assertSame(
            'fake-device-token',
            $message['token']
        );

        $this->assertSame(
            ['title' => 'Test title', 'body' => 'Test body'],
            $message['notification']
        );

        $this->assertSame([
            'type' => 'announcement',
            'active' => 'true',
            'count' => '5',
        ], $message['data']);
    }

    public function test_empty_device_token_is_rejected(): void
    {
        $credentials = new class('unused.json') extends GoogleCredentialsProvider
        {
            public function getAccessToken(): string
            {
                return 'test-access-token';
            }
        };

        $client = new FcmHttpV1Client(
            'test-project',
            $credentials,
            new Client
        );

        $this->expectException(InvalidArgumentException::class);

        $client->toToken('   ');
    }

    public function test_non_scalar_data_value_is_rejected(): void
    {
        $credentials = new class('unused.json') extends GoogleCredentialsProvider
        {
            public function getAccessToken(): string
            {
                return 'test-access-token';
            }
        };

        $client = new FcmHttpV1Client(
            'test-project',
            $credentials,
            new Client
        );

        $this->expectException(InvalidArgumentException::class);

        $client->toToken('fake-device-token')
            ->data(['metadata' => ['nested' => 'value']]);
    }
}
