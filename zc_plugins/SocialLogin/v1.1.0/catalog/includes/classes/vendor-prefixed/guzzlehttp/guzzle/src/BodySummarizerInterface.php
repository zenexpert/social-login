<?php

declare(strict_types=1);

namespace ZenExpert\SocialLogin\GuzzleHttp;

use ZenExpert\SocialLogin\Psr\Http\Message\MessageInterface;

interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message): ?string;
}
