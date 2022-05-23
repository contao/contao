<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Tests\Cron;

use Contao\Model\Collection;
use Contao\NewsletterBundle\Cron\PurgeSubscriptionsCron;
use Contao\NewsletterRecipientsModel;
use Contao\TestCase\ContaoTestCase;

class PurgeSubscriptionsCronTest extends ContaoTestCase
{
    public function testDeletesExpiredSubscriptions(): void
    {
        $commentsNotifyModel = $this->createMock(NewsletterRecipientsModel::class);
        $commentsNotifyModel
            ->expects($this->once())
            ->method('delete')
        ;

        $commentsNotifyModelAdapter = $this->mockAdapter(['findExpiredSubscriptions']);
        $commentsNotifyModelAdapter
            ->expects($this->once())
            ->method('findExpiredSubscriptions')
            ->willReturn(new Collection([$commentsNotifyModel], NewsletterRecipientsModel::getTable()))
        ;

        $framework = $this->mockContaoFramework([NewsletterRecipientsModel::class => $commentsNotifyModelAdapter]);

        (new PurgeSubscriptionsCron($framework, null))();
    }
}
