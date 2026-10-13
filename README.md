# Laravel Push Manager

A Laravel package for sending push notifications through Firebase Cloud Messaging (FCM) HTTP v1.

## Features

- Firebase service-account authentication
- FCM HTTP v1 message delivery
- Notification title and body
- Custom data payloads
- Direct message sending
- Optional Laravel queue dispatch
- Retryable and non-retryable FCM error classification
- Laravel service-provider auto-discovery
- Publishable configuration

## Requirements

- PHP 8.2 or later
- Laravel 11, 12, or 13, subject to compatibility testing
- A Firebase project with Firebase Cloud Messaging enabled
- A Firebase service-account JSON file

## Installation

Install the package through Composer:

```bash
composer require pialsoft/laravel-push-manager
```

If Laravel package auto-discovery is disabled in your application, register the provider in your application's provider configuration:

```php
Pialsoft\LaravelPushManager\PushManagerServiceProvider::class,
```

## Firebase setup

1. Open the Firebase Console and select your project.
2. Ensure Firebase Cloud Messaging is enabled for the project.
3. Open Project settings and the Service accounts section.
4. Generate a private key for the Firebase service account.
5. Store the downloaded JSON file securely inside your Laravel application.

For example, use:

```text
storage/app/firebase/service-account.json
```

Do not commit service-account JSON files to Git, publish them, or expose their contents to users. Ensure the application can read the file.

## Configuration

Publish the package configuration:

```bash
php artisan vendor:publish --tag=push-manager-config
```

This creates `config/push-manager.php`.

Add the following variables to your Laravel application's `.env` file:

```dotenv
FCM_PROJECT_ID=your-firebase-project-id
FCM_CREDENTIALS_FILE=storage/app/firebase/service-account.json
FCM_HTTP_TIMEOUT=10

PUSH_MANAGER_QUEUE_ENABLED=true
PUSH_MANAGER_QUEUE_CONNECTION=
PUSH_MANAGER_QUEUE=push-notifications
```

Replace `your-firebase-project-id` with the Firebase project ID.

`FCM_CREDENTIALS_FILE` is resolved relative to the Laravel application base path unless an absolute path is supplied.

The package requires both values to be present before it can send Firebase messages. If either value is empty, missing, or the credentials file cannot be read, the package throws a clear runtime exception when the Firebase client is resolved.

Keep credentials out of source control. Do not put the service-account JSON or private key directly into `.env`.

After changing configuration in a deployed application, refresh Laravel's configuration cache as appropriate:

```bash
php artisan config:clear
```

### Compatibility notes

This package targets Laravel 11, 12, and 13. Compatibility is verified against the supported Laravel versions in this repository's Composer constraints and the package's test matrix.

## Send a notification directly

```php
use Pialsoft\LaravelPushManager\Facades\PushManager;

$result = PushManager::toToken($deviceToken)
    ->title('Hello!')
    ->body('This is a push notification.')
    ->data([
        'type' => 'announcement',
        'id' => 123,
    ])
    ->send();
```

`$deviceToken` must be a valid FCM registration token.

The `send()` method sends the request immediately and returns the decoded FCM response on success. FCM data values must be scalar; booleans and numbers are converted to strings.

## Queue a notification

```php
use Pialsoft\LaravelPushManager\Facades\PushManager;

PushManager::toToken($deviceToken)
    ->title('Hello!')
    ->body('This notification is queued.')
    ->data([
        'type' => 'announcement',
    ])
    ->queue();
```

When `PUSH_MANAGER_QUEUE_ENABLED=true`, the package dispatches a `SendPushNotificationJob` to Laravel's queue.

When queue support is disabled, `queue()` sends the notification directly instead.

The `queue()` method returns `void`; it does not return the FCM message response.

### Run a queue worker

Configure a Laravel queue connection in the host application. For local development, the database queue can be used if its migration has been created and run.

Start a worker for the configured queue:

```bash
php artisan queue:work --queue=push-notifications
```

If `PUSH_MANAGER_QUEUE_CONNECTION` specifies a connection, configure that connection in the host application's `config/queue.php` and start the worker with the matching connection.

In production, run workers under a process supervisor and configure failed-job storage according to Laravel's queue documentation.

The job allows up to three attempts and uses backoff values of 10 and 30 seconds. Retryable FCM exceptions are rethrown for Laravel to handle. Non-retryable `FcmRequestException` instances are marked as failed.

## Error handling

FCM request failures are represented by `FcmRequestException`.

Available methods:

```php
$exception->httpStatus();
$exception->fcmErrorCode();
$exception->isRetryable();
$exception->isInvalidToken();
```

Catch exceptions around direct sends when your application needs custom logging or error handling:

```php
use Pialsoft\LaravelPushManager\Exceptions\FcmRequestException;
use Pialsoft\LaravelPushManager\Facades\PushManager;

try {
    $result = PushManager::toToken($deviceToken)
        ->title('Hello')
        ->body('Message body')
        ->send();
} catch (FcmRequestException $exception) {
    report($exception);

    if ($exception->isInvalidToken()) {
        // Your application can deactivate this device token.
    }
}
```

This package does not manage your application's device-token database or automatically delete invalid tokens.

## Testing

To run the package tests in a development checkout:

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
composer validate
```

## Security

- Never commit Firebase service-account JSON files or private keys.
- Keep device tokens and credentials out of public logs.
- Use appropriate file permissions for credentials.
- Use a queue connection suitable for your deployment.
- Protect notification endpoints with the host application's authentication and authorization policies.

## License

MIT. See the `LICENSE` file.
