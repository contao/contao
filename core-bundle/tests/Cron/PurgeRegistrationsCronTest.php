<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Cron;

use Contao\CoreBundle\Cron\PurgeRegistrationsCron;
use Contao\MemberModel;
use Contao\Model\Collection;
use Contao\TestCase\ContaoTestCase;

class PurgeRegistrationsCronTest extends ContaoTestCase
{
    public function testDeletesExpiredSubscriptions(): void
    {
        $memberModel = $this->createMock(MemberModel::class);
        $memberModel
            ->expects($this->once())
            ->method('delete')
        ;

        $memberModelAdapter = $this->mockAdapter(['findExpiredRegistrations']);
        $memberModelAdapter
            ->expects($this->once())
            ->method('findExpiredRegistrations')
            ->willReturn(new Collection([$memberModel], MemberModel::getTable()))
        ;

        $framework = $this->mockContaoFramework([MemberModel::class => $memberModelAdapter]);

        (new PurgeRegistrationsCron($framework, null))();
    }
}
