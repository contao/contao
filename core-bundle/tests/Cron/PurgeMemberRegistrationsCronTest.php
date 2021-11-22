<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\Cron;

use Contao\CoreBundle\Cron\PurgeMemberRegistrationsCron;
use Contao\CoreBundle\Tests\TestCase;
use Contao\MemberModel;
use Contao\Model\Collection;

class PurgeMemberRegistrationsCronTest extends TestCase
{
    public function testPurgesExpiredMemberRegistrations(): void
    {
        $member = $this->createMock(MemberModel::class);
        $member
            ->expects($this->once())
            ->method('delete')
        ;

        $memberModelAdapter = $this->mockAdapter(['findExpiredRegistrations', 'delete']);
        $memberModelAdapter
            ->expects($this->once())
            ->method('findExpiredRegistrations')
            ->willReturn(new Collection([$member], 'tl_member'))
        ;

        $framework = $this->mockContaoFramework([MemberModel::class => $memberModelAdapter]);

        (new PurgeMemberRegistrationsCron($framework))();
    }
}
