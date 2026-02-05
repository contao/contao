<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Backend\Event;

use Contao\CoreBundle\Search\Backend\Event\FormatTableDataContainerDocumentEvent;
use PHPUnit\Framework\TestCase;

class FormatTableDataContainerDocumentEventTest extends TestCase
{
    public function testGetters(): void
    {
        $value = 'foobar';
        $fieldConfig = ['foo' => 'bar'];

        $event = new FormatTableDataContainerDocumentEvent($value, $fieldConfig);

        $this->assertSame($value, $event->getValue());
        $this->assertSame($fieldConfig, $event->getFieldConfig());
        $this->assertSame($value, $event->getSearchableContent());
    }

    public function testSetSearchableContent(): void
    {
        $searchableContent = 'searchable content';
        $event = new FormatTableDataContainerDocumentEvent('foobar', []);

        $event->setSearchableContent($searchableContent);
        $this->assertSame($searchableContent, $event->getSearchableContent());
    }
}
