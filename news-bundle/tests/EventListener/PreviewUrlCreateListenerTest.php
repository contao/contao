<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\EventListener;

use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\NewsBundle\EventListener\PreviewUrlCreateListener;
use Contao\NewsModel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PreviewUrlCreateListenerTest extends ContaoTestCase
{
    public function testCreatesThePreviewUrl(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $event = new PreviewUrlCreateEvent('news', 1);

        $newsModel = $this->mockClassWithProperties(NewsModel::class);
        $newsModel->id = 1;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findByPk' => $newsModel]),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $listener = new PreviewUrlCreateListener($requestStack, $framework);
        $listener($event);

        $this->assertSame('news=1', $event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlIfTheFrameworkIsNotInitialized(): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $event = new PreviewUrlCreateEvent('news', 1);

        $listener = new PreviewUrlCreateListener(new RequestStack(), $framework);
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlIfTheNewsParameterIsNotSet(): void
    {
        $framework = $this->mockContaoFramework();
        $event = new PreviewUrlCreateEvent('calendar', 1);

        $listener = new PreviewUrlCreateListener(new RequestStack(), $framework);
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlOnTheArchiveListPage(): void
    {
        $request = new Request();
        $request->query->set('table', 'tl_news');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework = $this->mockContaoFramework();
        $event = new PreviewUrlCreateEvent('news', 1);

        $listener = new PreviewUrlCreateListener($requestStack, $framework);
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    public function testOverwritesTheIdIfTheArchiveSettingsAreEdited(): void
    {
        $request = new Request();
        $request->query->set('act', 'edit');
        $request->query->set('table', 'tl_news');
        $request->query->set('id', 2);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $newsModel = $this->mockClassWithProperties(NewsModel::class);
        $newsModel->id = 2;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findByPk' => $newsModel]),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $event = new PreviewUrlCreateEvent('news', 1);

        $listener = new PreviewUrlCreateListener($requestStack, $framework);
        $listener($event);

        $this->assertSame('news=2', $event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlIfThereIsNoNewsItem(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findByPk' => null]),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $event = new PreviewUrlCreateEvent('news', 0);

        $listener = new PreviewUrlCreateListener($requestStack, $framework);
        $listener($event);

        $this->assertNull($event->getQuery());
    }
}
