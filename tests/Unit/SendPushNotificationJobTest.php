<?php

namespace Pialsoft\LaravelPushManager\Tests\Unit;

use Illuminate\Contracts\Queue\Job as QueueJob;
use PHPUnit\Framework\TestCase;
use Pialsoft\LaravelPushManager\Client\FcmHttpV1Client;
use Pialsoft\LaravelPushManager\Exceptions\FcmRequestException;
use Pialsoft\LaravelPushManager\Jobs\SendPushNotificationJob;

class SendPushNotificationJobTest extends TestCase
{
    public function test_retryable_fcm_exception_is_rethrown(): void
    {
        $message = ['token' => 'fake-device-token'];

        $exception = new FcmRequestException(
            'Service temporarily unavailable.',
            503,
            'UNAVAILABLE',
            true,
            false
        );

        $client = $this->createMock(FcmHttpV1Client::class);

        $client->expects($this->once())
            ->method('send')
            ->with($message)
            ->willThrowException($exception);

        $job = new SendPushNotificationJob($message);

        try {
            $job->handle($client);
            $this->fail('Expected retryable exception to be rethrown.');
        } catch (FcmRequestException $thrown) {
            $this->assertSame($exception, $thrown);
        }
    }

    public function test_non_retryable_fcm_exception_fails_the_job(): void
    {
        $message = ['token' => 'fake-device-token'];

        $exception = new FcmRequestException(
            'Invalid request.',
            400,
            'INVALID_ARGUMENT',
            false,
            false
        );

        $client = $this->createMock(FcmHttpV1Client::class);

        $client->expects($this->once())
            ->method('send')
            ->with($message)
            ->willThrowException($exception);

        $queueJob = $this->createMock(QueueJob::class);

        $queueJob->expects($this->once())
            ->method('fail')
            ->with($exception);

        $job = new SendPushNotificationJob($message);
        $job->setJob($queueJob);

        $job->handle($client);
    }

    public function test_job_has_expected_retry_configuration(): void
    {
        $job = new SendPushNotificationJob([
            'token' => 'fake-device-token',
        ]);

        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 30], $job->backoff);
    }
}
