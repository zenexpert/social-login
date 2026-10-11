<?php
declare(strict_types=1);

namespace ZenExpert\SocialLogin\Lcobucci\Clock;

use DateTimeImmutable;
use ZenExpert\SocialLogin\StellaMaris\Clock\ClockInterface;

interface Clock extends ClockInterface
{
    public function now(): DateTimeImmutable;
}
