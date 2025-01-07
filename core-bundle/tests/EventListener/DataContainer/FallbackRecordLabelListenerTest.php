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

use Contao\Config;
use Contao\CoreBundle\Event\DataContainerRecordLabelEvent;
use Contao\CoreBundle\EventListener\DataContainer\FallbackRecordLabelListener;
use Contao\CoreBundle\Tests\Fixtures\TranslatorStub;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\DcaLoader;
use Contao\System;
use Symfony\Component\Translation\MessageCatalogueInterface;

class FallbackRecordLabelListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        (new \ReflectionClass(DcaLoader::class))->setStaticPropertyValue('arrLoaded', []);
        unset($GLOBALS['TL_DCA'], $GLOBALS['TL_MIME']);
        $this->resetStaticProperties([System::class, Config::class]);

        parent::tearDown();
    }

    public function testIgnoresOtherIdentifiers(): void
    {
        $listener = new FallbackRecordLabelListener($this->createMock(TranslatorStub::class));
        $listener($event = new DataContainerRecordLabelEvent('contao.something.tl_foo.123', ['id' => 123]));

        $this->assertNull($event->getLabel());
    }

    public function testGetsLabelFromTranslator(): void
    {
        $GLOBALS['TL_DCA']['tl_foo']['list']['sorting']['mode'] = DataContainer::MODE_PARENT;
        $GLOBALS['TL_DCA']['tl_foo']['list']['sorting']['child_record_callback'] = static fn () => null;

        System::setContainer($this->getContainerWithContaoConfiguration());
        (new \ReflectionClass(DcaLoader::class))->setStaticPropertyValue('arrLoaded', ['dcaFiles' => ['tl_foo' => true]]);

        $catalogue = $this->createMock(MessageCatalogueInterface::class);
        $catalogue
            ->method('has')
            ->with('tl_foo.edit', 'contao_tl_foo')
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
            ->with('tl_foo.edit', [123], 'contao_tl_foo')
            ->willReturn('Edit 123')
        ;

        $listener = new FallbackRecordLabelListener($translator);
        $listener($event = new DataContainerRecordLabelEvent('contao.db.tl_foo.123', ['id' => 123]));

        $this->assertSame('Edit 123', $event->getLabel());
    }

    public function testGetsLabelFromDcaWithShowColumnsEnabled(): void
    {
        $GLOBALS['TL_DCA']['tl_foo']['list']['label']['showColumns'] = true;
        $GLOBALS['TL_DCA']['tl_foo']['list']['label']['fields'] = ['fieldA', 'fieldB'];

        System::setContainer($this->getContainerWithContaoConfiguration());
        (new \ReflectionClass(DcaLoader::class))->setStaticPropertyValue('arrLoaded', ['dcaFiles' => ['tl_foo' => true]]);

        $translator = $this->createMock(TranslatorStub::class);

        $listener = new FallbackRecordLabelListener($translator);
        $listener($event = new DataContainerRecordLabelEvent('contao.db.tl_foo.123', ['id' => 123, 'fieldA' => 'A', 'fieldB' => '<span>(B &amp; B)</span>']));

        $this->assertSame('A (B & B)', $event->getLabel());
    }
}
