<?php

namespace ZenExpert\SocialLogin\League\OAuth2\Client\Test;

use ZenExpert\SocialLogin\Composer\InstalledVersions;
use ZenExpert\SocialLogin\Composer\Semver\VersionParser;

if (!InstalledVersions::satisfies(new VersionParser(), 'lcobucci/jwt', '^1 || ^2 || ^3')) {
    require_once __DIR__ . '/../ext/KeyDumpSigner8.php';
} else {
    require_once __DIR__ . '/../ext/KeyDumpSigner5.php';
}
