<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CommentsBundle\Cron;

use Contao\CommentsNotifyModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Psr\Log\LoggerInterface;

class PurgeSubscriptionsCronJob
{
    public function __construct(private ContaoFramework $framework, private LoggerInterface|null $logger)
    {
    }

    public function __invoke(): void
    {
        $this->framework->initialize();

        $subscriptions = $this->framework->getAdapter(CommentsNotifyModel::class)->findExpiredSubscriptions();

        if (null === $subscriptions) {
            return;
        }

        /** @var CommentsNotifyModel $subscription */
        foreach ($subscriptions as $subscription) {
            $subscription->delete();
        }

        if (null !== $this->logger) {
            $this->logger->info('Purged the unactivated comment subscriptions');
        }
    }
}
