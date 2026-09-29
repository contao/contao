<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthServerBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\OAuthServerBundle\Repository\OAuthTokenRepository;

#[AsCronJob('daily')]
class PurgeExpiredOAuthTokensCron
{
    public function __construct(private readonly OAuthTokenRepository $tokenRepository)
    {
    }

    public function __invoke(): void
    {
        $this->tokenRepository->purgeExpired(new \DateTimeImmutable('-1 day'));
    }
}
