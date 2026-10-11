<?php

declare(strict_types=1);

namespace ZenExpert\SocialLogin\GuzzleHttp\Exception;

/**
 * Exception thrown when redirect middleware reaches the redirect limit.
 */
class TooManyRedirectsException extends ResponseException
{
}
