<?php

namespace Pialsoft\LaravelPushManager\Client;

use Google\Auth\Credentials\ServiceAccountCredentials;
use JsonException;
use RuntimeException;

class GoogleCredentialsProvider
{
    public function __construct(
        private readonly string $credentialsFile
    ) {}

    public function getAccessToken(): string
    {
        if (! is_file($this->credentialsFile)) {
            throw new RuntimeException(
                'Firebase service-account credentials file was not found.'
            );
        }

        if (! is_readable($this->credentialsFile)) {
            throw new RuntimeException(
                'Firebase service-account credentials file is not readable.'
            );
        }

        try {
            $key = json_decode(
                file_get_contents($this->credentialsFile),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Firebase service-account credentials contain invalid JSON.',
                0,
                $exception
            );
        }

        if (
            ! is_array($key)
            || ($key['type'] ?? null) !== 'service_account'
            || ! is_string($key['client_email'] ?? null)
            || trim($key['client_email']) === ''
            || ! is_string($key['private_key'] ?? null)
            || trim($key['private_key']) === ''
        ) {
            throw new RuntimeException(
                'Firebase service-account credentials are missing required fields.'
            );
        }

        $credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/firebase.messaging'],
            $key
        );

        $token = $credentials->fetchAuthToken();

        if (empty($token['access_token'])) {
            throw new RuntimeException(
                'Unable to obtain a Firebase OAuth access token.'
            );
        }

        return $token['access_token'];
    }
}
