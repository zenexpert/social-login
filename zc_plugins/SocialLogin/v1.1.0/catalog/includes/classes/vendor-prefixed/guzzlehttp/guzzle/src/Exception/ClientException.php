<?php

declare(strict_types=1);

namespace ZenExpert\SocialLogin\GuzzleHttp\Exception;

/**
 * Exception thrown for HTTP responses with 4xx status codes.
 */
class ClientException extends BadResponseException
{
}
