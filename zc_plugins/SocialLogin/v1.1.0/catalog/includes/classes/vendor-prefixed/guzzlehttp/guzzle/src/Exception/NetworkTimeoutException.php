<?php

declare(strict_types=1);

namespace ZenExpert\SocialLogin\GuzzleHttp\Exception;

/**
 * Exception thrown when a transfer times out before response headers are
 * received.
 */
class NetworkTimeoutException extends NetworkException
{
}
