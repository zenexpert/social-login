<?php
declare(strict_types=1);

namespace ZenExpert\SocialLogin\Lcobucci\JWT\Validation;

use ZenExpert\SocialLogin\Lcobucci\JWT\Exception;
use RuntimeException;

final class NoConstraintsGiven extends RuntimeException implements Exception
{
}
