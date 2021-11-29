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
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\ServiceAnnotation\CronJob;
use Contao\MemberModel;
use Psr\Log\LoggerInterface;

/**
 * @CronJob("hourly")
 */
class PurgeMemberRegistrationsCron
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ContaoFramework $framework, LoggerInterface $logger = null)
    {
        $this->framework = $framework;
        $this->logger = $logger;
    }

    public function __invoke(): void
    {
        $this->framework->initialize();

        /** @var MemberModel $memberModel */
        $memberModel = $this->framework->getAdapter(MemberModel::class);

        $members = $memberModel->findExpiredRegistrations();

        if (null === $members) {
            return;
        }

        $count = $members->count();

        foreach ($members as $member) {
            $member->delete();
        }

        if (null !== $this->logger) {
            $this->logger->info(sprintf('Purged %s unactivated member registrations', $count), ['contao' => new ContaoContext(__METHOD__, ContaoContext::CRON)]);
        }
    }
}
