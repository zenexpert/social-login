<?php
declare(strict_types=1);

namespace ZenExpert\SocialLogin\Lcobucci\JWT\Signer\Ecdsa;

use ZenExpert\SocialLogin\Lcobucci\JWT\Signer\Ecdsa;

use const OPENSSL_ALGO_SHA256;

final class Sha256 extends Ecdsa
{
    public function algorithmId(): string
    {
        return 'ES256';
    }

    public function algorithm(): int
    {
        return OPENSSL_ALGO_SHA256;
    }

    public function keyLength(): int
    {
        return 64;
    }
}
