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

use Contao\CoreBundle\Cron\PurgeOptInTokensCron;
use Contao\CoreBundle\OptIn\OptIn;
use Contao\TestCase\ContaoTestCase;

class PurgeOptInTokensCronTest extends ContaoTestCase
{
    public function testPurgesOptInTokens(): void
    {
        $optIn = $this->createMock(OptIn::class);
        $optIn
            ->expects($this->once())
            ->method('purgeTokens')
        ;

        (new PurgeOptInTokensCron($optIn, null))();
    }
}
