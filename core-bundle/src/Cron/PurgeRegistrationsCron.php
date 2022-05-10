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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\CronJob;
use Contao\MemberModel;
use Psr\Log\LoggerInterface;

/**
 * @CronJob("daily")
 */
class PurgeRegistrationsCron
{
    public function __construct(private ContaoFramework $framework, private LoggerInterface|null $logger)
    {
    }

    public function __invoke(): void
    {
        $this->framework->initialize();

        $members = $this->framework->getAdapter(MemberModel::class)->findExpiredRegistrations();

        if (null === $members) {
            return;
        }

        /** @var MemberModel $member */
        foreach ($members as $member) {
            $member->delete();
        }

        $this->logger?->info('Purged the unactivated member registrations');
    }
}
