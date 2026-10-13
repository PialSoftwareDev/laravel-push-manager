<?php

namespace Pialsoft\LaravelPushManager\Client;

use Google\Auth\Credentials\ServiceAccountCredentials;
use RuntimeException;

class GoogleCredentialsProvider
{
    public function __construct(
        private readonly string $credentialsFile
    ) {
    }

    public function getAccessToken(): string
    {
        if (! is_file($this->credentialsFile)) {
            throw new RuntimeException(
                'Firebase service-account credentials file was not found.'
            );
        }

        $credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/firebase.messaging'],
            $this->credentialsFile
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
