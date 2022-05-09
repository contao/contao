<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Tests\EventListener;

use Contao\Model\Collection;
use Contao\NewsletterBundle\Cron\PurgeSubscriptionsCronJob;
use Contao\NewsletterRecipientsModel;
use Contao\TestCase\ContaoTestCase;

class PurgeSubscriptionsCronJobTest extends ContaoTestCase
{
    public function testDeletesExpiredSubscriptions(): void
    {
        $commentsNotifyModel = $this->createMock(NewsletterRecipientsModel::class);
        $commentsNotifyModel
            ->expects($this->exactly(1))
            ->method('delete')
        ;

        $commentsNotifyModelAdapter = $this->mockAdapter(['findExpiredSubscriptions']);
        $commentsNotifyModelAdapter
            ->expects($this->exactly(1))
            ->method('findExpiredSubscriptions')
            ->willReturn(new Collection([$commentsNotifyModel], NewsletterRecipientsModel::getTable()))
        ;

        $framework = $this->mockContaoFramework([NewsletterRecipientsModel::class => $commentsNotifyModelAdapter]);

        (new PurgeSubscriptionsCronJob($framework, null))();
    }
}
