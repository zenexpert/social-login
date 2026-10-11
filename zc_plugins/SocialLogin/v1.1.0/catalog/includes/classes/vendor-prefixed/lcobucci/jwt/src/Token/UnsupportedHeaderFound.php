<?php
declare(strict_types=1);

namespace ZenExpert\SocialLogin\Lcobucci\JWT\Token;

use InvalidArgumentException;
use ZenExpert\SocialLogin\Lcobucci\JWT\Exception;

final class UnsupportedHeaderFound extends InvalidArgumentException implements Exception
{
    public static function encryption(): self
    {
        return new self('Encryption is not supported yet');
    }
}
