<?php

namespace Pialsoft\LaravelPushManager\Facades;

use Illuminate\Support\Facades\Facade;
use Pialsoft\LaravelPushManager\Client\FcmHttpV1Client;
use Pialsoft\LaravelPushManager\Messages\PushMessage;

/**
 * @method static PushMessage toToken(string $token)
 *
 * @see FcmHttpV1Client
 */
class PushManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FcmHttpV1Client::class;
    }
}
