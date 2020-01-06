<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\ArticleModel;
use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\EventListener\PreviewUrlConvertListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Request;

class PreviewUrlConverterListenerTest extends ContaoTestCase
{
    public function testConvertsThePreviewUrlWithUrl(): void
    {
        $request = new Request();
        $request->query->set('url', 'en/content-elements.html');

        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener($this->mockContaoFramework());
        $listener($event);

        $this->assertSame('/en/content-elements.html', $event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfTheFrameworkIsNotInitialized(): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $event = new PreviewUrlConvertEvent(new Request());

        $listener = new PreviewUrlConvertListener($framework);
        $listener($event);

        $this->assertNull($event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlWithoutParameter(): void
    {
        $request = new Request();
        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener($this->mockContaoFramework());
        $listener($event);

        $this->assertNull($event->getUrl());
    }

    public function testConvertsThePreviewUrlWithPage(): void
    {
        $request = new Request();
        $request->query->set('page', '9');

        $event = new PreviewUrlConvertEvent($request);
        $pageModel = $this->createConfiguredMock(PageModel::class, ['getPreviewUrl' => '/en/content-elements.html']);

        $adapters = [
            PageModel::class => $this->mockConfiguredAdapter(['findWithDetails' => $pageModel]),
            ArticleModel::class => $this->mockConfiguredAdapter(['findByAlias' => null]),
        ];

        $framework = $this->mockContaoFramework($adapters);

        $listener = new PreviewUrlConvertListener($framework);
        $listener($event);

        $this->assertSame('/en/content-elements.html', $event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfThereIsNoPage(): void
    {
        $request = new Request();
        $event = new PreviewUrlConvertEvent($request);

        $adapters = [
            PageModel::class => $this->mockConfiguredAdapter(['findWithDetails' => null]),
        ];

        $framework = $this->mockContaoFramework($adapters);

        $listener = new PreviewUrlConvertListener($framework);
        $listener($event);

        $this->assertNull($event->getUrl());
    }
}
