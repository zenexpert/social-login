<?php

declare(strict_types=1);

namespace ZenExpert\SocialLogin\GuzzleHttp\Exception;

/**
 * Exception thrown when connection establishment exceeds the time limit.
 */
class ConnectTimeoutException extends ConnectException
{
}
