<?php

namespace Pialsoft\LaravelPushManager\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Pialsoft\LaravelPushManager\Client\FcmHttpV1Client;
use Pialsoft\LaravelPushManager\Exceptions\FcmRequestException;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30];

    public function __construct(
        public readonly array $message
    ) {}

    public function handle(FcmHttpV1Client $client): void
    {
        try {
            $client->send($this->message);
        } catch (FcmRequestException $exception) {
            if (! $exception->isRetryable()) {
                $this->fail($exception);

                return;
            }

            throw $exception;
        }
    }
}
