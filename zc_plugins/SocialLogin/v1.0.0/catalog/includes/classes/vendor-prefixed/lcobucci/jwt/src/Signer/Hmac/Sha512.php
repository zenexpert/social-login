<?php
declare(strict_types=1);

namespace ZenExpert\SocialLogin\Lcobucci\JWT\Signer\Hmac;

use ZenExpert\SocialLogin\Lcobucci\JWT\Signer\Hmac;

final class Sha512 extends Hmac
{
    public function algorithmId(): string
    {
        return 'HS512';
    }

    public function algorithm(): string
    {
        return 'sha512';
    }
}
