<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Event;

use Contao\CoreBundle\Event\FilterPageTypeEvent;
use Contao\DataContainer;
use PHPUnit\Framework\TestCase;

class FilterPageTypeEventTest extends TestCase
{
    /**
     * @group legacy
     */
    public function testReturnsDataContainer(): void
    {
        $dc = $this->createMock(DataContainer::class);
        $event = new FilterPageTypeEvent([], $dc);

        $this->assertSame($dc, $event->getDataContainer());
    }

    /**
     * @group legacy
     */
    public function testReturnsOptionsWithNumericKeys(): void
    {
        $dc = $this->createMock(DataContainer::class);
        $event = new FilterPageTypeEvent(['foo' => 'bar'], $dc);

        $this->assertSame(['bar'], $event->getOptions());
    }

    /**
     * @group legacy
     */
    public function testCanAddOption(): void
    {
        $dc = $this->createMock(DataContainer::class);
        $event = new FilterPageTypeEvent([], $dc);

        $this->assertSame([], $event->getOptions());

        $event->addOption('foo');

        $this->assertSame(['foo'], $event->getOptions());
    }

    /**
     * @group legacy
     */
    public function testDoesNotAddDuplicateOptions(): void
    {
        $dc = $this->createMock(DataContainer::class);
        $event = new FilterPageTypeEvent(['foo'], $dc);

        $this->assertSame(['foo'], $event->getOptions());

        $event->addOption('foo');

        $this->assertSame(['foo'], $event->getOptions());
    }

    /**
     * @group legacy
     */
    public function testCanSetOptions(): void
    {
        $dc = $this->createMock(DataContainer::class);
        $event = new FilterPageTypeEvent(['foo'], $dc);

        $this->assertSame(['foo'], $event->getOptions());

        $event->setOptions(['foo' => 'bar']);

        $this->assertSame(['bar'], $event->getOptions());
    }

    /**
     * @group legacy
     */
    public function testCanRemoveOption(): void
    {
        $dc = $this->createMock(DataContainer::class);
        $event = new FilterPageTypeEvent(['foo'], $dc);

        $this->assertSame(['foo'], $event->getOptions());

        $event->removeOption('foo');

        $this->assertSame([], $event->getOptions());
    }

    /**
     * @group legacy
     */
    public function testIgnoresMissingWhenRemovingOptions(): void
    {
        $dc = $this->createMock(DataContainer::class);
        $event = new FilterPageTypeEvent(['foo'], $dc);

        $this->assertSame(['foo'], $event->getOptions());

        $event->removeOption('bar');

        $this->assertSame(['foo'], $event->getOptions());
    }
}
