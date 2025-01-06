<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Backend\EventListener;

use Contao\CoreBundle\Search\Backend\Event\FormatTableDataContainerDocumentEvent;
use Contao\CoreBundle\Search\Backend\EventListener\FormatCoreWidgetSearchResultsListener;
use PHPUnit\Framework\TestCase;

class FormatCoreWidgetSearchResultsListenerTest extends TestCase
{
    public function testInvokeWithTextarea(): void
    {
        $event = new FormatTableDataContainerDocumentEvent('<p>Some <b>bold</b> content</p>', ['inputType' => 'textarea']);
        $listener = new FormatCoreWidgetSearchResultsListener();
        $listener($event);
        $this->assertSame('Some bold content', $event->getSearchableContent());
    }

    public function testInvokeWithInputUnit(): void
    {
        $event = new FormatTableDataContainerDocumentEvent(serialize(['value' => 'foobar', 'unit' => 'h1']), ['inputType' => 'inputUnit']);
        $listener = new FormatCoreWidgetSearchResultsListener();
        $listener($event);
        $this->assertSame('foobar', $event->getSearchableContent());
    }

    public function testInvokeWithUnhandledInputType(): void
    {
        $value = 'foobar';
        $event = new FormatTableDataContainerDocumentEvent($value, ['inputType' => 'unknown']);
        $listener = new FormatCoreWidgetSearchResultsListener();
        $listener($event);
        $this->assertSame($value, $event->getSearchableContent());
    }

    public function testInvokeWithEmptyFieldConfig(): void
    {
        $value = 'foobar';
        $event = new FormatTableDataContainerDocumentEvent($value, []);
        $listener = new FormatCoreWidgetSearchResultsListener();
        $listener($event);
        $this->assertSame($value, $event->getSearchableContent());
    }

    public function testInvokeWithInvalidSerializedDataInInputUnit(): void
    {
        $value = 'invalid serialized content';
        $event = new FormatTableDataContainerDocumentEvent($value, ['inputType' => 'inputUnit']);
        $listener = new FormatCoreWidgetSearchResultsListener();
        $listener($event);
        $this->assertSame($value, $event->getSearchableContent());
    }
}
