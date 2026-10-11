<?php

namespace ZenExpert\SocialLogin\League\OAuth2\Client\Test\Provider;

use ZenExpert\SocialLogin\Lcobucci\JWT\Configuration;
use ZenExpert\SocialLogin\Lcobucci\JWT\Signer\Key\InMemory;
use ZenExpert\SocialLogin\League\OAuth2\Client\Provider\Apple;
use ZenExpert\SocialLogin\League\OAuth2\Client\Test\KeyDumpSigner;

/**
 * Class TestApple
 * @package League\OAuth2\Client\Test\Provider
 * @author Patrick Bußmann <patrick.bussmann@bussmann-it.de>
 */
class TestApple extends Apple
{
    /**
     * {@inheritDoc}
     */
    public function getConfiguration()
    {
        return Configuration::forSymmetricSigner(
            new KeyDumpSigner(),
            InMemory::plainText('private')
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalKey()
    {
        return null;
    }
}
