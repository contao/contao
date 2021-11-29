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

use Contao\CoreBundle\Cron\PurgeOptInTokensCron;
use Contao\CoreBundle\OptIn\OptIn;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;

class PurgeOptInTokensCronTest extends TestCase
{
    public function testPurgesExpiredMemberRegistrations(): void
    {
        $optIn = $this->createMock(OptIn::class);
        $optIn
            ->expects($this->once())
            ->method('purgeTokens')
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('fetchOne')
            ->willReturn('1')
        ;

        (new PurgeOptInTokensCron($optIn, $connection))();
    }
}
