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
use Contao\CoreBundle\DataContainer\ValueFormatter;
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
        $listener = new FallbackRecordLabelListener($this->createStub(TranslatorStub::class), $this->createStub(ValueFormatter::class));
        $listener($event = new DataContainerRecordLabelEvent('contao.something.tl_foo.123', ['id' => 123]));

        $this->assertNull($event->getLabel());
    }

    public function testGetsLabelFromTranslator(): void
    {
        System::setContainer($this->getContainerWithContaoConfiguration());
        (new \ReflectionClass(DcaLoader::class))->setStaticPropertyValue('arrLoaded', ['dcaFiles' => ['tl_foo' => true]]);

        $catalogue = $this->createStub(MessageCatalogueInterface::class);
        $catalogue
            ->method('has')
            ->willReturnMap([['tl_foo.edit.1', 'contao_tl_foo', true]])
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
            ->with('tl_foo.edit.1', [123], 'contao_tl_foo')
            ->willReturn('Edit 123')
        ;

        $listener = new FallbackRecordLabelListener($translator, $this->createStub(ValueFormatter::class));
        $listener($event = new DataContainerRecordLabelEvent('contao.db.tl_foo.123', ['id' => 123]));

        $this->assertSame('Edit 123', $event->getLabel());
    }

    public function testGetsLabelFromDcaWithDefaultSearchField(): void
    {
        $GLOBALS['TL_DCA']['tl_foo']['list']['sorting']['defaultSearchField'] = 'fieldA';

        System::setContainer($this->getContainerWithContaoConfiguration());
        (new \ReflectionClass(DcaLoader::class))->setStaticPropertyValue('arrLoaded', ['dcaFiles' => ['tl_foo' => true]]);

        $translator = $this->createStub(TranslatorStub::class);

        $formatter = $this->createMock(ValueFormatter::class);
        $formatter
            ->expects($this->once())
            ->method('format')
            ->with('tl_foo', 'fieldA', 'A <span>(B &amp; B)</span>', null)
            ->willReturn('A (B & B)')
        ;

        $listener = new FallbackRecordLabelListener($translator, $formatter);
        $listener($event = new DataContainerRecordLabelEvent('contao.db.tl_foo.123', ['id' => 123, 'fieldA' => 'A <span>(B &amp; B)</span>']));

        $this->assertSame('A (B & B)', $event->getLabel());
    }

    public function testGetsLabelFromDcaWithDateFlaggedDefaultSearchField(): void
    {
        $GLOBALS['TL_DCA']['tl_foo']['list']['sorting']['defaultSearchField'] = 'fieldA';
        $GLOBALS['TL_DCA']['tl_foo']['fields']['fieldA']['flag'] = DataContainer::SORT_MONTH_ASC;
        $GLOBALS['TL_DCA']['tl_foo']['fields']['fieldA']['eval']['rgxp'] = 'date';

        System::setContainer($this->getContainerWithContaoConfiguration());
        (new \ReflectionClass(DcaLoader::class))->setStaticPropertyValue('arrLoaded', ['dcaFiles' => ['tl_foo' => true]]);

        $translator = $this->createStub(TranslatorStub::class);

        $formatter = $this->createMock(ValueFormatter::class);
        $formatter
            ->expects($this->once())
            ->method('format')
            ->with('tl_foo', 'fieldA', '1772131097', null)
            ->willReturn('2026-02-26')
        ;

        $listener = new FallbackRecordLabelListener($translator, $formatter);
        $listener($event = new DataContainerRecordLabelEvent('contao.db.tl_foo.123', ['id' => 123, 'fieldA' => '1772131097']));

        $this->assertSame('2026-02-26', $event->getLabel());
    }
}
