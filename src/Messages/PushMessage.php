<?php

namespace Pialsoft\LaravelPushManager\Messages;

use InvalidArgumentException;
use Pialsoft\LaravelPushManager\Client\FcmHttpV1Client;
use Pialsoft\LaravelPushManager\Jobs\SendPushNotificationJob;

class PushMessage
{
    private string $token;

    private array $notification = [];

    private array $data = [];

    public function __construct(
        private readonly FcmHttpV1Client $client,
        string $token
    ) {
        if (trim($token) === '') {
            throw new InvalidArgumentException(
                'FCM device token must not be empty.'
            );
        }

        $this->token = $token;
    }

    public function title(string $title): self
    {
        $this->notification['title'] = $title;

        return $this;
    }

    public function body(string $body): self
    {
        $this->notification['body'] = $body;

        return $this;
    }

    public function data(array $data): self
    {
        foreach ($data as $key => $value) {
            if (! is_string($key) || ! is_scalar($value)) {
                throw new InvalidArgumentException(
                    'FCM data keys must be strings and values must be scalar.'
                );
            }

            if (is_bool($value)) {
                $data[$key] = $value ? 'true' : 'false';
            } elseif (is_int($value) || is_float($value)) {
                $data[$key] = (string) $value;
            }
        }

        $this->data = $data;

        return $this;
    }

    public function send(): array
    {
        return $this->client->send($this->buildMessage());
    }

    public function queue(): void
    {
        $message = $this->buildMessage();

        if (! config('push-manager.queue.enabled', true)) {
            $this->client->send($message);

            return;
        }

        $job = new SendPushNotificationJob($message);

        $connection = config('push-manager.queue.connection');
        $queue = config('push-manager.queue.name', 'push-notifications');

        if (is_string($connection) && trim($connection) !== '') {
            $job->onConnection($connection);
        }

        if (is_string($queue) && trim($queue) !== '') {
            $job->onQueue($queue);
        }

        dispatch($job);
    }

    private function buildMessage(): array
    {
        $message = [
            'token' => $this->token,
        ];

        if ($this->notification !== []) {
            $message['notification'] = $this->notification;
        }

        if ($this->data !== []) {
            $message['data'] = $this->data;
        }

        return $message;
    }
}
