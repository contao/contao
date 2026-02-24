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

use Contao\CoreBundle\Event\LayoutEvent;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Twig\LayoutTemplate;
use Contao\LayoutModel;
use Contao\Model\Collection;
use Contao\NewsBundle\EventListener\AddFeedsFromLayoutListener;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AddFeedsFromLayoutListenerTest extends ContaoTestCase
{
    public function testAddsTheNewsFeedLink(): void
    {
        $newsFeedPage = $this->createStub(PageModel::class);
        $newsFeedPage
            ->method('__get')
            ->willReturnMap([
                ['id', 42],
                ['type', 'news_feed'],
                ['alias', 'news'],
                ['title', 'Latest news'],
                ['feedFormat', 'rss'],
            ])
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->exactly(2))
            ->method('generate')
            ->with($newsFeedPage, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://localhost/news.xml')
        ;

        $htmlHeadBag = new HtmlHeadBag();

        $responseContext = new ResponseContext();
        $responseContext->add($htmlHeadBag);

        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseContextAccessor
            ->expects($this->once())
            ->method('getResponseContext')
            ->willReturn($responseContext)
        ;

        $layoutModel = $this->createClassWithPropertiesStub(LayoutModel::class);
        $layoutModel->newsfeeds = 'a:1:{i:0;i:3;}';

        $currentPage = $this->createStub(PageModel::class);
        $collection = new Collection([$newsFeedPage], 'tl_page');

        $adapters = [
            PageModel::class => $this->createConfiguredAdapterStub(['findMultipleByIds' => $collection]),
        ];

        $listener = new AddFeedsFromLayoutListener($this->createContaoFrameworkStub($adapters), $urlGenerator, $responseContextAccessor);
        $listener->onLayoutEvent(new LayoutEvent(new LayoutTemplate('<foobar>', static fn () => new Response('<content>')), $currentPage, $layoutModel, $responseContext));

        $this->assertSame(' type="rss" rel="alternate" href="http://localhost/news.xml" title="Latest news"', implode('', $htmlHeadBag->getLinkTags()));

        $htmlHeadBag->setLinkTags([]);
        $listener->onGeneratePage($currentPage, $layoutModel);

        $this->assertSame(' type="rss" rel="alternate" href="http://localhost/news.xml" title="Latest news"', implode('', $htmlHeadBag->getLinkTags()));
    }

    public function testDoesNotAddTheNewsFeedLinkIfThereAreNoFeeds(): void
    {
        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method('generate')
        ;

        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseContextAccessor
            ->expects($this->never())
            ->method('getResponseContext')
        ;

        $htmlHeadBag = new HtmlHeadBag();

        $responseContext = new ResponseContext();
        $responseContext->add($htmlHeadBag);

        $layoutModel = $this->createClassWithPropertiesStub(LayoutModel::class);
        $layoutModel->newsfeeds = '';

        $currentPage = $this->createStub(PageModel::class);

        $listener = new AddFeedsFromLayoutListener($this->createContaoFrameworkStub(), $urlGenerator, $responseContextAccessor);
        $listener->onLayoutEvent(new LayoutEvent(new LayoutTemplate('<foobar>', static fn () => new Response('<content>')), $currentPage, $layoutModel, $responseContext));

        $this->assertSame([], $htmlHeadBag->getLinkTags());
    }

    public function testDoesNotAddTheNewsFeedLinkIfThereAreNoValidModels(): void
    {
        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method('generate')
        ;

        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseContextAccessor
            ->expects($this->never())
            ->method('getResponseContext')
        ;

        $htmlHeadBag = new HtmlHeadBag();

        $responseContext = new ResponseContext();
        $responseContext->add($htmlHeadBag);

        $layoutModel = $this->createClassWithPropertiesStub(LayoutModel::class);
        $layoutModel->newsfeeds = 'a:1:{i:0;i:3;}';

        $currentPage = $this->createStub(PageModel::class);

        $regularPage = $this->createClassWithPropertiesStub(PageModel::class, ['type' => 'regular']);
        $collection = new Collection([$regularPage], 'tl_page');

        $adapters = [
            PageModel::class => $this->createConfiguredAdapterStub(['findMultipleByIds' => $collection]),
        ];

        $listener = new AddFeedsFromLayoutListener($this->createContaoFrameworkStub($adapters), $urlGenerator, $responseContextAccessor);
        $listener->onLayoutEvent(new LayoutEvent(new LayoutTemplate('<foobar>', static fn () => new Response('<content>')), $currentPage, $layoutModel, $responseContext));

        $this->assertSame([], $htmlHeadBag->getLinkTags());
    }

    public function testDoesNotAddTheNewsFeedLinkIfNoResponseContext(): void
    {
        $framework = $this->createContaoFrameworkMock();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseContextAccessor
            ->expects($this->once())
            ->method('getResponseContext')
            ->willReturn(null)
        ;

        $listener = new AddFeedsFromLayoutListener(
            $framework,
            $this->createStub(ContentUrlGenerator::class),
            $responseContextAccessor,
        );

        $listener->onLayoutEvent(
            new LayoutEvent(
                new LayoutTemplate('<foobar>', static fn () => new Response('<content>')),
                $this->createStub(PageModel::class),
                $this->createStub(LayoutModel::class),
                null,
            ),
        );

        $listener
            ->onGeneratePage(
                $this->createStub(PageModel::class),
                $this->createStub(LayoutModel::class),
            )
        ;
    }
}
