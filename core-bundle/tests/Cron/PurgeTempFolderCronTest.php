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

use Contao\Automator;
use Contao\CoreBundle\Cron\PurgeTempFolderCron;
use Contao\TestCase\ContaoTestCase;

class PurgeTempFolderCronTest extends ContaoTestCase
{
    public function testExecutesPurgeTempFolder(): void
    {
        $automator = $this->createMock(Automator::class);
        $automator
            ->expects($this->once())
            ->method('purgeTempFolder')
        ;

        $framework = $this->mockContaoFramework([], [Automator::class => $automator]);

        (new PurgeTempFolderCron($framework))();
    }
}
