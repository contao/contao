<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\Widget;

use Contao\ApiBundle\Widget\WidgetConverterInterface;
use Contao\ApiBundle\Widget\WidgetConverterRegistry;
use PHPUnit\Framework\TestCase;

class WidgetConverterRegistryTest extends TestCase
{
    public function testSelectsOnlyTheFirstSupportingConverter(): void
    {
        $config = ['inputType' => 'thirdParty'];
        $unsupported = $this->createMock(WidgetConverterInterface::class);
        $unsupported
            ->expects($this->once())
            ->method('supports')
            ->with($config)
            ->willReturn(false)
        ;
        $supported = $this->createMock(WidgetConverterInterface::class);
        $supported
            ->expects($this->once())
            ->method('supports')
            ->with($config)
            ->willReturn(true)
        ;
        $later = $this->createMock(WidgetConverterInterface::class);
        $later
            ->expects($this->never())
            ->method('supports')
        ;

        $registry = new WidgetConverterRegistry([$unsupported, $supported, $later]);

        $this->assertSame($supported, $registry->get($config));
    }

    public function testReturnsNullWhenNoConverterSupportsTheField(): void
    {
        $converter = $this->createStub(WidgetConverterInterface::class);
        $converter
            ->method('supports')
            ->willReturn(false)
        ;

        $this->assertNull(new WidgetConverterRegistry([$converter])->get(['inputType' => 'unknown']));
        $this->assertNull(new WidgetConverterRegistry([])->get([]));
    }
}
