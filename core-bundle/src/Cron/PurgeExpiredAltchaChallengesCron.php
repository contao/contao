<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\Repository\AltchaRepository;

#[AsCronJob('hourly')]
class PurgeExpiredAltchaChallengesCron
{
    public function __construct(private readonly AltchaRepository $repository)
    {
    }

    public function __invoke(): void
    {
        $this->repository->purgeExpiredChallenges();
    }
}
