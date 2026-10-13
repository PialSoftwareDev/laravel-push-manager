<?php

namespace Pialsoft\LaravelPushManager\Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;
use Pialsoft\LaravelPushManager\Client\FcmHttpV1Client;
use Pialsoft\LaravelPushManager\Client\GoogleCredentialsProvider;
use Pialsoft\LaravelPushManager\Jobs\SendPushNotificationJob;
use Pialsoft\LaravelPushManager\PushManagerServiceProvider;

class PushMessageQueueTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PushManagerServiceProvider::class,
        ];
    }

    public function test_queue_dispatches_job_to_configured_queue(): void
    {
        Queue::fake();

        config([
            'push-manager.queue.enabled' => true,
            'push-manager.queue.connection' => 'redis',
            'push-manager.queue.name' => 'push-tests',
        ]);

        $message = $this->makeClient()->toToken('fake-device-token')
            ->title('Test title')
            ->body('Test body')
            ->data(['type' => 'announcement']);

        $message->queue();

        Queue::assertPushed(
            SendPushNotificationJob::class,
            function (SendPushNotificationJob $job): bool {
                $this->assertSame('redis', $job->connection);
                $this->assertSame('push-tests', $job->queue);
                $this->assertSame([
                    'token' => 'fake-device-token',
                    'notification' => [
                        'title' => 'Test title',
                        'body' => 'Test body',
                    ],
                    'data' => [
                        'type' => 'announcement',
                    ],
                ], $job->message);

                return true;
            }
        );
    }

    public function test_disabled_queue_sends_directly_without_dispatching_job(): void
    {
        Queue::fake();

        config([
            'push-manager.queue.enabled' => false,
        ]);

        $history = [];

        $handler = HandlerStack::create(new MockHandler([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"name":"projects/test-project/messages/test123"}'
            ),
        ]));

        $handler->push(Middleware::history($history));

        $client = $this->makeClient(
            new Client(['handler' => $handler])
        );

        $result = $client->toToken('fake-device-token')
            ->title('Direct title')
            ->body('Direct body')
            ->queue();

        Queue::assertNothingPushed();

        $this->assertCount(1, $history);
        $this->assertSame('POST', $history[0]['request']->getMethod());
    }

    private function makeClient(?Client $http = null): FcmHttpV1Client
    {
        $credentials = new class('unused.json') extends GoogleCredentialsProvider
        {
            public function getAccessToken(): string
            {
                return 'test-access-token';
            }
        };

        return new FcmHttpV1Client(
            'test-project',
            $credentials,
            $http ?? new Client
        );
    }
}
