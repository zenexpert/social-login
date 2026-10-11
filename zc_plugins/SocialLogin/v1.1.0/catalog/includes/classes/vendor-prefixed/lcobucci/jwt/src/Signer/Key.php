<?php
declare(strict_types=1);

namespace ZenExpert\SocialLogin\Lcobucci\JWT\Signer;

interface Key
{
    public function contents(): string;

    public function passphrase(): string;
}
