<?php

declare(strict_types=1);

namespace ZenExpert\SocialLogin\GuzzleHttp\Exception;

/**
 * Exception thrown when a transfer times out after response headers are
 * received.
 */
class ResponseTimeoutException extends ResponseTransferException
{
}
