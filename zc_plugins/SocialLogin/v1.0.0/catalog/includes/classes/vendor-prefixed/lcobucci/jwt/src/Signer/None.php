<?php
declare(strict_types=1);

namespace ZenExpert\SocialLogin\Lcobucci\JWT\Signer;

use ZenExpert\SocialLogin\Lcobucci\JWT\Signer;

final class None implements Signer
{
    public function algorithmId(): string
    {
        return 'none';
    }

    // @phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    public function sign(string $payload, Key $key): string
    {
        return '';
    }

    // @phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    public function verify(string $expected, string $payload, Key $key): bool
    {
        return $expected === '';
    }
}
