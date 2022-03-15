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

        $page = $this->mockClassWithProperties(PageModel::class);
        $page->enableCanonical = '';

        $pageModelAdapter = $this->mockAdapter(['findWithDetails']);
        $pageModelAdapter
            ->expects($this->once())
            ->method('findWithDetails')
            ->with(1)
            ->willReturn($page)
        ;

        $imageAdapter = $this->mockAdapter(['getHtml']);
        $imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('show.svg', '', 'title="disabled"')
            ->willReturn('<img src="show.svg" alt="" title="disabled">')
        ;

        $framework = $this->mockContaoFramework([
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

        $dc = $this->mockClassWithProperties(DataContainer::class);
        $dc->id = 1;
        $dc->table = 'tl_page';
        $dc->field = 'canonicalLink';

        $listener = new DisableCanonicalFieldsListener($framework, $translator);
        $listener('', $dc);

        $this->assertInstanceOf(\Closure::class, $GLOBALS['TL_DCA']['tl_page']['fields']['canonicalLink']['xlabel'][0]);
        $this->assertSame('<img src="show.svg" alt="" title="disabled">', $GLOBALS['TL_DCA']['tl_page']['fields']['canonicalLink']['xlabel'][0]());
    }

    public function testDoesNotDisableTheFieldIfCanonicalUrlsAreEnabled(): void
    {
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->enableCanonical = '1';

        $pageModelAdapter = $this->mockAdapter(['findWithDetails']);
        $pageModelAdapter
            ->expects($this->once())
            ->method('findWithDetails')
            ->with(1)
            ->willReturn($page)
        ;

        $imageAdapter = $this->mockAdapter(['getHtml']);
        $imageAdapter
            ->expects($this->never())
            ->method('getHtml')
        ;

        $framework = $this->mockContaoFramework([
            PageModel::class => $pageModelAdapter,
            Image::class => $imageAdapter,
        ]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->never())
            ->method('trans')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class);
        $dc->id = 1;
        $dc->table = 'tl_page';
        $dc->field = 'canonicalLink';

        $listener = new DisableCanonicalFieldsListener($framework, $translator);
        $listener('', $dc);
    }

    public function testDoesNotDisableTheFieldIfThePageModelCannotBeFound(): void
    {
        $pageModelAdapter = $this->mockAdapter(['findWithDetails']);
        $pageModelAdapter
            ->expects($this->once())
            ->method('findWithDetails')
            ->with(1)
            ->willReturn(null)
        ;

        $imageAdapter = $this->mockAdapter(['getHtml']);
        $imageAdapter
            ->expects($this->never())
            ->method('getHtml')
        ;

        $framework = $this->mockContaoFramework([
            PageModel::class => $pageModelAdapter,
            Image::class => $imageAdapter,
        ]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->never())
            ->method('trans')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class);
        $dc->id = 1;
        $dc->table = 'tl_page';
        $dc->field = 'canonicalLink';

        $listener = new DisableCanonicalFieldsListener($framework, $translator);
        $listener('', $dc);
    }

    public function testDoesNotDisableTheFieldIfThereIsNoId(): void
    {
        $GLOBALS['TL_DCA']['tl_page']['fields']['canonicalLink']['eval'] = [];

        $pageModelAdapter = $this->mockAdapter(['findWithDetails']);
        $pageModelAdapter
            ->expects($this->never())
            ->method('findWithDetails')
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageModelAdapter]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->never())
            ->method('trans')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class);

        $listener = new DisableCanonicalFieldsListener($framework, $translator);
        $listener('', $dc);
    }
}
