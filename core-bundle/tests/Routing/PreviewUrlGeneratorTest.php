<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing;

use Contao\ArticleModel;
use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\PreviewUrlGenerator;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PreviewUrlGeneratorTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        \define('CURRENT_ID', 42);
    }

    public function testReturnsTheDefaultUrlIfThereIsNoQueryParameter(): void
    {
        $generator = $this->getGenerator(new Request());

        $this->assertSame('/contao/preview', $generator->getPreviewUrl());
    }

    public function testReturnsAPagePreviewUrl(): void
    {
        $request = new Request();
        $request->query->set('do', 'page');

        $generator = $this->getGenerator($request);

        $this->assertSame('/contao/preview?page=42', $generator->getPreviewUrl());
    }

    public function testReturnsAnArticlePreviewUrl(): void
    {
        $request = new Request();
        $request->query->set('do', 'article');

        /** @var ArticleModel $article */
        $article = $this->mockClassWithProperties(ArticleModel::class);
        $article->pid = 3;

        $adapter = $this->mockAdapter(['findByPk']);
        $adapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(42)
            ->willReturn($article)
        ;

        $framework = $this->mockContaoFramework([ArticleModel::class => $adapter]);
        $generator = $this->getGenerator($request, $framework);

        $this->assertSame('/contao/preview?page=3', $generator->getPreviewUrl());
    }

    public function testReturnsTheDefaultUrlIfThereIsNoArticleModel(): void
    {
        $request = new Request();
        $request->query->set('do', 'article');

        $adapter = $this->mockAdapter(['findByPk']);
        $adapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(42)
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([ArticleModel::class => $adapter]);
        $generator = $this->getGenerator($request, $framework);

        $this->assertSame('/contao/preview', $generator->getPreviewUrl());
    }

    public function testDispatchesAnEventIfTheQueryParameterIsUnknown(): void
    {
        $request = new Request();
        $request->query->set('do', 'news');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(
                    static function (PreviewUrlCreateEvent $event): bool {
                        $event->setQuery('news=42');

                        return true;
                    }
                ),
                ContaoCoreEvents::PREVIEW_URL_CREATE
            )
        ;

        $generator = $this->getGenerator($request, null, $eventDispatcher);

        $this->assertSame('/contao/preview?news=42', $generator->getPreviewUrl());
    }

    public function testFailsIfTheRequestStackIsEmpty(): void
    {
        $generator = new PreviewUrlGenerator(
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(RouterInterface::class),
            new RequestStack(),
            $this->mockContaoFramework()
        );

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The request stack did not contain a request');

        $generator->getPreviewUrl();
    }

    private function getGenerator(Request $request = null, ContaoFramework $framework = null, EventDispatcherInterface $eventDispatcher = null): PreviewUrlGenerator
    {
        $requestStack = new RequestStack();

        if (null !== $request) {
            $requestStack->push($request);
        }

        if (null === $framework) {
            $framework = $this->mockContaoFramework();
        }

        if (null === $eventDispatcher) {
            $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
            $eventDispatcher
                ->expects($this->never())
                ->method('dispatch')
            ;
        }

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend_preview')
            ->willReturn('/contao/preview')
        ;

        return new PreviewUrlGenerator($eventDispatcher, $router, $requestStack, $framework);
    }
}
