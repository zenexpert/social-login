<?php

namespace ZenExpert\SocialLogin\League\OAuth2\Client\Test;

use ZenExpert\SocialLogin\Lcobucci\JWT\Signature;
use ZenExpert\SocialLogin\Lcobucci\JWT\Signer;

final class KeyDumpSigner implements Signer
{
    public function getAlgorithmId()
    {
        return 'keydump';
    }

    public function modifyHeader(array &$headers)
    {
        $headers['alg'] = $this->getAlgorithmId();
    }

    public function verify($expected, $payload, $key)
    {
        return $expected === $key->contents();
    }

    public function sign($payload, $key)
    {
        return new Signature($key->contents());
    }
}
