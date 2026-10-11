<?php
declare(strict_types=1);

namespace ZenExpert\SocialLogin\Lcobucci\JWT\Signer\Hmac;

use ZenExpert\SocialLogin\Lcobucci\JWT\Signer\Hmac;

final class Sha384 extends Hmac
{
    public function algorithmId(): string
    {
        return 'HS384';
    }

    public function algorithm(): string
    {
        return 'sha384';
    }
}
