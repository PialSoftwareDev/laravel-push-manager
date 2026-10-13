<?php

namespace Pialsoft\LaravelPushManager\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Pialsoft\LaravelPushManager\Client\FcmHttpV1Client;
use Pialsoft\LaravelPushManager\Client\GoogleCredentialsProvider;
use Pialsoft\LaravelPushManager\Exceptions\FcmRequestException;

class FcmHttpV1ClientTest extends TestCase
{
    private function makeClient(
        MockHandler $mock
    ): FcmHttpV1Client {
        $credentials = new class('unused.json')
            extends GoogleCredentialsProvider {
            public function getAccessToken(): string
            {
                return 'test-access-token';
            }
        };

        $http = new Client([
            'handler' => HandlerStack::create($mock),
        ]);

        return new FcmHttpV1Client(
            'test-project',
            $credentials,
            $http
        );
    }

    public function test_successful_message_send(): void
    {
        $mock = new MockHandler([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'name' => 'projects/test-project/messages/abc123',
                ])
            ),
        ]);

        $client = $this->makeClient($mock);

        $result = $client->send([
            'token' => 'fake-device-token',
            'notification' => [
                'title' => 'Test title',
                'body' => 'Test message',
            ],
        ]);

        $this->assertSame(
            'projects/test-project/messages/abc123',
            $result['name']
        );
    }

    public function test_unregistered_token_is_identified(): void
    {
        $mock = new MockHandler([
            new Response(
                404,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'error' => [
                        'code' => 404,
                        'message' => 'Requested entity was not found.',
                        'status' => 'NOT_FOUND',
                        'details' => [
                            [
                                'errorCode' => 'UNREGISTERED',
                                '@type' =>
                                    'type.googleapis.com/google.firebase.fcm.v1.FcmError',
                            ],
                        ],
                    ],
                ])
            ),
        ]);

        $client = $this->makeClient($mock);

        try {
            $client->send(['token' => 'invalid-device-token']);
            $this->fail('Expected FcmRequestException was not thrown.');
        } catch (FcmRequestException $exception) {
            $this->assertSame(404, $exception->httpStatus());
            $this->assertSame(
                'UNREGISTERED',
                $exception->fcmErrorCode()
            );
            $this->assertTrue($exception->isInvalidToken());
            $this->assertFalse($exception->isRetryable());
        }
    }

    public function test_service_unavailable_is_retryable(): void
    {
        $mock = new MockHandler([
            new Response(
                503,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'error' => [
                        'code' => 503,
                        'message' => 'Service unavailable',
                        'status' => 'UNAVAILABLE',
                    ],
                ])
            ),
        ]);

        $client = $this->makeClient($mock);

        try {
            $client->send(['token' => 'fake-device-token']);
            $this->fail('Expected FcmRequestException was not thrown.');
        } catch (FcmRequestException $exception) {
            $this->assertSame(503, $exception->httpStatus());
            $this->assertTrue($exception->isRetryable());
            $this->assertFalse($exception->isInvalidToken());
        }
    }

    public function test_invalid_argument_is_not_retryable(): void
    {
        $mock = new MockHandler([
            new Response(
                400,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'error' => [
                        'code' => 400,
                        'message' => 'Invalid request.',
                        'status' => 'INVALID_ARGUMENT',
                    ],
                ])
            ),
        ]);

        $client = $this->makeClient($mock);

        try {
            $client->send(['token' => 'fake-device-token']);
            $this->fail('Expected FcmRequestException was not thrown.');
        } catch (FcmRequestException $exception) {
            $this->assertSame(400, $exception->httpStatus());
            $this->assertFalse($exception->isRetryable());
        }
    }
}
