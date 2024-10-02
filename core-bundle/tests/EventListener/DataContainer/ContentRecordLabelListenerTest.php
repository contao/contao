<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\Event\DataContainerRecordLabelEvent;
use Contao\CoreBundle\EventListener\DataContainer\ContentRecordLabelListener;
use Contao\CoreBundle\Tests\Fixtures\TranslatorStub;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Translation\MessageCatalogueInterface;

class ContentRecordLabelListenerTest extends TestCase
{
    public function testIgnoresOtherTables(): void
    {
        $listener = new ContentRecordLabelListener($this->createMock(TranslatorStub::class));
        $listener($event = new DataContainerRecordLabelEvent('contao.db.tl_foo.123', ['id' => 123]));

        $this->assertNull($event->getLabel());
    }

    public function testGetsLabelFromTranslator(): void
    {
        $catalogue = $this->createMock(MessageCatalogueInterface::class);
        $catalogue
            ->method('has')
            ->with('CTE.foo.0', 'contao_default')
            ->willReturn(true)
        ;

        $translator = $this->createMock(TranslatorStub::class);
        $translator
            ->expects($this->once())
            ->method('getCatalogue')
            ->willReturn($catalogue)
        ;

        $translator
            ->expects($this->once())
            ->method('trans')
            ->with('CTE.foo.0', [], 'contao_default')
            ->willReturn('My Label')
        ;

        $listener = new ContentRecordLabelListener($translator);
        $listener($event = new DataContainerRecordLabelEvent('contao.db.tl_content.123', ['id' => 123, 'type' => 'foo']));

        $this->assertSame('My Label', $event->getLabel());
    }
}
