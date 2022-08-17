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

use Contao\CoreBundle\Cron\PurgePreviewLinksCron;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;

class PurgePreviewLinksCronTest extends ContaoTestCase
{
    public function testPurgesPreviewLinks(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with('DELETE FROM tl_preview_link WHERE createdAt<=UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 31 DAY))')
        ;

        $cron = new PurgePreviewLinksCron($connection);
        $cron();
    }
}
