<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\NewsletterRecipientsModel;
use Psr\Log\LoggerInterface;

#[AsCronJob('daily')]
class PurgeSubscriptionsCron
{
    public function __construct(
        private ContaoFramework $framework,
        private LoggerInterface|null $logger,
    ) {
    }

    public function __invoke(): void
    {
        $this->framework->initialize();

        $subscriptions = $this->framework->getAdapter(NewsletterRecipientsModel::class)->findExpiredSubscriptions();

        if (null === $subscriptions) {
            return;
        }

        /** @var NewsletterRecipientsModel $subscription */
        foreach ($subscriptions as $subscription) {
            $subscription->delete();
        }

        $this->logger?->info('Purged the unactivated newsletter subscriptions');
    }
}
