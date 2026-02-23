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

use Contao\CoreBundle\EventListener\DataContainer\DisableCanonicalFieldsListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\Image;
use Contao\PageModel;
use Symfony\Contracts\Translation\TranslatorInterface;

class DisableCanonicalFieldsListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testDisablesTheField(): void
    {
        $GLOBALS['TL_DCA']['tl_page']['fields']['canonicalLink']['eval'] = [];

        $page = $this->createClassWithPropertiesStub(PageModel::class);
        $page->enableCanonical = false;

        $pageModelAdapter = $this->createAdapterMock(['findWithDetails']);
        $pageModelAdapter
            ->expects($this->once())
            ->method('findWithDetails')
            ->with(1)
            ->willReturn($page)
        ;

        $imageAdapter = $this->createAdapterMock(['getHtml']);
        $imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('info.svg', 'disabled', 'data-contao--tooltips-target="tooltip"')
            ->willReturn('<img src="info.svg" alt="disabled" data-contao--tooltips-target="tooltip">')
        ;

        $framework = $this->createContaoFrameworkStub([
            PageModel::class => $pageModelAdapter,
            Image::class => $imageAdapter,
        ]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->with('tl_page.relCanonical', [], 'contao_tl_page')
            ->willReturn('disabled')
        ;

        $dc = $this->createClassWithPropertiesStub(DataContainer::class);
        $dc->id = 1;
        $dc->table = 'tl_page';
        $dc->field = 'canonicalLink';

        $listener = new DisableCanonicalFieldsListener($framework, $translator);
        $listener('', $dc);

        $this->assertInstanceOf(\Closure::class, $GLOBALS['TL_DCA']['tl_page']['fields']['canonicalLink']['xlabel'][0]);
        $this->assertSame(' <img src="info.svg" alt="disabled" data-contao--tooltips-target="tooltip">', $GLOBALS['TL_DCA']['tl_page']['fields']['canonicalLink']['xlabel'][0]());
    }

    public function testDoesNotDisableTheFieldIfCanonicalUrlsAreEnabled(): void
    {
        $page = $this->createClassWithPropertiesStub(PageModel::class);
        $page->enableCanonical = true;

        $pageModelAdapter = $this->createAdapterMock(['findWithDetails']);
        $pageModelAdapter
            ->expects($this->once())
            ->method('findWithDetails')
            ->with(1)
            ->willReturn($page)
        ;

        $imageAdapter = $this->createAdapterMock(['getHtml']);
        $imageAdapter
            ->expects($this->never())
            ->method('getHtml')
        ;

        $framework = $this->createContaoFrameworkStub([
            PageModel::class => $pageModelAdapter,
            Image::class => $imageAdapter,
        ]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->never())
            ->method('trans')
        ;

        $dc = $this->createClassWithPropertiesStub(DataContainer::class);
        $dc->id = 1;
        $dc->table = 'tl_page';
        $dc->field = 'canonicalLink';

        $listener = new DisableCanonicalFieldsListener($framework, $translator);
        $listener('', $dc);
    }

    public function testDoesNotDisableTheFieldIfThePageModelCannotBeFound(): void
    {
        $pageModelAdapter = $this->createAdapterMock(['findWithDetails']);
        $pageModelAdapter
            ->expects($this->once())
            ->method('findWithDetails')
            ->with(1)
            ->willReturn(null)
        ;

        $imageAdapter = $this->createAdapterMock(['getHtml']);
        $imageAdapter
            ->expects($this->never())
            ->method('getHtml')
        ;

        $framework = $this->createContaoFrameworkStub([
            PageModel::class => $pageModelAdapter,
            Image::class => $imageAdapter,
        ]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->never())
            ->method('trans')
        ;

        $dc = $this->createClassWithPropertiesStub(DataContainer::class);
        $dc->id = 1;
        $dc->table = 'tl_page';
        $dc->field = 'canonicalLink';

        $listener = new DisableCanonicalFieldsListener($framework, $translator);
        $listener('', $dc);
    }

    public function testDoesNotDisableTheFieldIfThereIsNoId(): void
    {
        $GLOBALS['TL_DCA']['tl_page']['fields']['canonicalLink']['eval'] = [];

        $pageModelAdapter = $this->createAdapterMock(['findWithDetails']);
        $pageModelAdapter
            ->expects($this->never())
            ->method('findWithDetails')
        ;

        $framework = $this->createContaoFrameworkStub([PageModel::class => $pageModelAdapter]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->never())
            ->method('trans')
        ;

        $dc = $this->createClassWithPropertiesStub(DataContainer::class);

        $listener = new DisableCanonicalFieldsListener($framework, $translator);
        $listener('', $dc);
    }
}
