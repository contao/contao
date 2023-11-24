<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CommentsBundle\Tests\Cron;

use Contao\CommentsBundle\Cron\PurgeSubscriptionsCron;
use Contao\CommentsNotifyModel;
use Contao\Model\Collection;
use Contao\TestCase\ContaoTestCase;

class PurgeSubscriptionsCronTest extends ContaoTestCase
{
    public function testDeletesExpiredSubscriptions(): void
    {
        $commentsNotifyModel = $this->createMock(CommentsNotifyModel::class);
        $commentsNotifyModel
            ->expects($this->once())
            ->method('delete')
        ;

        $commentsNotifyModelAdapter = $this->mockAdapter(['findExpiredSubscriptions']);
        $commentsNotifyModelAdapter
            ->expects($this->once())
            ->method('findExpiredSubscriptions')
            ->willReturn(new Collection([$commentsNotifyModel], CommentsNotifyModel::getTable()))
        ;

        $framework = $this->mockContaoFramework([CommentsNotifyModel::class => $commentsNotifyModelAdapter]);

        (new PurgeSubscriptionsCron($framework, null))();
    }
}
