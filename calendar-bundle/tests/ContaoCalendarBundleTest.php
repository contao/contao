<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Tests;

use Contao\CalendarBundle\ContaoCalendarBundle;
use Contao\CalendarBundle\DependencyInjection\Compiler\StartStopValidatorPass;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContaoCalendarBundleTest extends TestCase
{
    public function testAddsTheCompilerPass(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects($this->once())
            ->method('addCompilerPass')
            ->with(
                $this->callback(static function ($param) {
                    return StartStopValidatorPass::class === \get_class($param);
                })
            )
        ;

        $bundle = new ContaoCalendarBundle();
        $bundle->build($container);
    }
}
