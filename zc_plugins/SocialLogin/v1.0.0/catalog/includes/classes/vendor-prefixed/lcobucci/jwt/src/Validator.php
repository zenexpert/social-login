<?php
declare(strict_types=1);

namespace ZenExpert\SocialLogin\Lcobucci\JWT;

use ZenExpert\SocialLogin\Lcobucci\JWT\Validation\Constraint;
use ZenExpert\SocialLogin\Lcobucci\JWT\Validation\NoConstraintsGiven;
use ZenExpert\SocialLogin\Lcobucci\JWT\Validation\RequiredConstraintsViolated;

interface Validator
{
    /**
     * @throws RequiredConstraintsViolated
     * @throws NoConstraintsGiven
     */
    public function assert(Token $token, Constraint ...$constraints): void;

    /** @throws NoConstraintsGiven */
    public function validate(Token $token, Constraint ...$constraints): bool;
}
