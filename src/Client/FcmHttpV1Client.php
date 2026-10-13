<?php

namespace Pialsoft\LaravelPushManager\Client;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use InvalidArgumentException;
use Pialsoft\LaravelPushManager\Exceptions\FcmRequestException;
use Pialsoft\LaravelPushManager\Messages\PushMessage;
use RuntimeException;

class FcmHttpV1Client
{
    private ClientInterface $http;

    public function __construct(
        private readonly string $projectId,
        private readonly GoogleCredentialsProvider $credentials,
        ?ClientInterface $http = null,
        private readonly int $timeout = 10
    ) {
        if (trim($this->projectId) === '') {
            throw new InvalidArgumentException(
                'Firebase project ID must not be empty.'
            );
        }

        if ($this->timeout < 1) {
            throw new InvalidArgumentException(
                'HTTP timeout must be at least one second.'
            );
        }

        $this->http = $http ?? new Client;
    }

    public function toToken(string $token): PushMessage
    {
        return new PushMessage($this, $token);
    }

    public function send(array $message): array
    {
        $response = $this->http->request(
            'POST',
            sprintf(
                'https://fcm.googleapis.com/v1/projects/%s/messages:send',
                $this->projectId
            ),
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->credentials->getAccessToken(),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'message' => $message,
                ],
                'timeout' => $this->timeout,
                'http_errors' => false,
            ]
        );

        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $result = json_decode($body, true);

        if ($status < 200 || $status >= 300) {
            $error = is_array($result)
                ? ($result['error'] ?? [])
                : [];

            $errorCode = $this->extractErrorCode($error);
            $messageText = is_string($error['message'] ?? null)
                ? $error['message']
                : 'Firebase Cloud Messaging request failed.';

            $invalidToken = $errorCode === 'UNREGISTERED';

            $retryable = in_array(
                $status,
                [429, 500, 502, 503, 504],
                true
            ) || in_array(
                $errorCode,
                [
                    'INTERNAL',
                    'UNAVAILABLE',
                    'QUOTA_EXCEEDED',
                    'RESOURCE_EXHAUSTED',
                ],
                true
            );

            throw new FcmRequestException(
                $messageText,
                $status,
                $errorCode,
                $retryable,
                $invalidToken
            );
        }

        if (! is_array($result)) {
            throw new RuntimeException(
                'Firebase returned an invalid JSON response.'
            );
        }

        return $result;
    }

    private function extractErrorCode(array $error): ?string
    {
        foreach (($error['details'] ?? []) as $detail) {
            if (
                is_array($detail)
                && isset($detail['errorCode'])
                && is_string($detail['errorCode'])
            ) {
                return $detail['errorCode'];
            }
        }

        $status = $error['status'] ?? null;

        return is_string($status) ? $status : null;
    }
}
