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

use Contao\Controller;
use Contao\CoreBundle\Event\SitemapEvent;
use Contao\CoreBundle\Framework\Adapter;
use Contao\Model\Collection;
use Contao\News;
use Contao\NewsArchiveModel;
use Contao\NewsBundle\EventListener\SitemapListener;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Request;

class SitemapListenerTest extends ContaoTestCase
{
    public function testDoesNotAddAnythingWithoutArchives(): void
    {
        $sitemapEvent = new SitemapEvent($this->createSitemap(), new Request(), [1]);

        $adapters = [NewsArchiveModel::class => $this->mockConfiguredAdapter(['findAll' => null])];

        (new SitemapListener($this->mockContaoFramework($adapters)))($sitemapEvent);

        $this->assertSame(0, \count($sitemapEvent->getDocument()->firstChild->childNodes));
    }

    public function testAddsNewsUrlToSitemap(): void
    {
        $article = $this->mockClassWithProperties(NewsModel::class, ['robots' => 'index,follow']);
        $articles = new Collection([$article], 'tl_news');

        $newsModelAdapter = $this->mockAdapter(['findPublishedDefaultByPid']);
        $newsModelAdapter
            ->expects($this->exactly(1))
            ->method('findPublishedDefaultByPid')
            ->willReturn($articles)
        ;

        $newsAdapter = $this->mockAdapter(['generateNewsUrl']);
        $newsAdapter
            ->expects($this->exactly(1))
            ->method('generateNewsUrl')
            ->willReturn('http://domain.tld/news/the-foobar-news.html')
        ;

        $adapters = [
            NewsArchiveModel::class => $this->getNewsArchiveModelAdapter(),
            PageModel::class => $this->getPageModelAdapter(),
            Controller::class => $this->getControllerAdapter(2),
            NewsModel::class => $newsModelAdapter,
            News::class => $newsAdapter,
        ];

        $sitemapEvent = new SitemapEvent($this->createSitemap(), new Request(), [1]);

        (new SitemapListener($this->mockContaoFramework($adapters)))($sitemapEvent);

        $sitemap = $sitemapEvent->getDocument();

        $this->assertSame('http://domain.tld/news/the-foobar-news.html', $sitemap->firstChild->firstChild->firstChild->nodeValue);
    }

    public function testDoesNotAddNewsUrlIfArchiveIsProtected(): void
    {
        $adapters = [
            NewsArchiveModel::class => $this->getNewsArchiveModelAdapter(['protected' => '1']),
            PageModel::class => new Adapter(PageModel::class),
            Controller::class => $this->getControllerAdapter(1),
            NewsModel::class => new Adapter(NewsModel::class),
            News::class => new Adapter(News::class),
        ];

        $sitemapEvent = new SitemapEvent($this->createSitemap(), new Request(), [1]);

        (new SitemapListener($this->mockContaoFramework($adapters)))($sitemapEvent);

        $this->assertSame(0, \count($sitemapEvent->getDocument()->firstChild->childNodes));
    }

    public function testDoesNotAddNewsUrlIfTargetPageIsNotPublished(): void
    {
        $pageModelAdapter = $this->mockAdapter(['findPublishedById']);
        $pageModelAdapter
            ->expects($this->exactly(1))
            ->method('findPublishedById')
            ->willReturn(null)
        ;

        $adapters = [
            NewsArchiveModel::class => $this->getNewsArchiveModelAdapter(),
            PageModel::class => $pageModelAdapter,
            Controller::class => $this->getControllerAdapter(1),
            NewsModel::class => new Adapter(NewsModel::class),
            News::class => new Adapter(News::class),
        ];

        $sitemapEvent = new SitemapEvent($this->createSitemap(), new Request(), [1]);

        (new SitemapListener($this->mockContaoFramework($adapters)))($sitemapEvent);

        $this->assertSame(0, \count($sitemapEvent->getDocument()->firstChild->childNodes));
    }

    public function testDoesNotAddNewsUrlIfTargetPageNotInRootIds(): void
    {
        $adapters = [
            NewsArchiveModel::class => $this->getNewsArchiveModelAdapter(),
            PageModel::class => $this->getPageModelAdapter(),
            Controller::class => $this->getControllerAdapter(1),
            NewsModel::class => new Adapter(NewsModel::class),
            News::class => new Adapter(News::class),
        ];

        $sitemapEvent = new SitemapEvent($this->createSitemap(), new Request(), [2]);

        (new SitemapListener($this->mockContaoFramework($adapters)))($sitemapEvent);

        $this->assertSame(0, \count($sitemapEvent->getDocument()->firstChild->childNodes));
    }

    public function testDoesNotAddNewsUrlIfTargetPageIsProtected(): void
    {
        $adapters = [
            NewsArchiveModel::class => $this->getNewsArchiveModelAdapter(),
            PageModel::class => $this->getPageModelAdapter(['protected' => '1']),
            Controller::class => $this->getControllerAdapter(2),
            NewsModel::class => new Adapter(NewsModel::class),
            News::class => new Adapter(News::class),
        ];

        $sitemapEvent = new SitemapEvent($this->createSitemap(), new Request(), [1]);

        (new SitemapListener($this->mockContaoFramework($adapters)))($sitemapEvent);

        $this->assertSame(0, \count($sitemapEvent->getDocument()->firstChild->childNodes));
    }

    public function testDoesNotAddNewsUrlIfTargetPageIsNotInSitemap(): void
    {
        $adapters = [
            NewsArchiveModel::class => $this->getNewsArchiveModelAdapter(),
            PageModel::class => $this->getPageModelAdapter(['robots' => 'noindex,nofollow']),
            Controller::class => $this->getControllerAdapter(2),
            NewsModel::class => new Adapter(NewsModel::class),
            News::class => new Adapter(News::class),
        ];

        $sitemapEvent = new SitemapEvent($this->createSitemap(), new Request(), [1]);

        (new SitemapListener($this->mockContaoFramework($adapters)))($sitemapEvent);

        $this->assertSame(0, \count($sitemapEvent->getDocument()->firstChild->childNodes));
    }

    public function testDoesNotAddNewsUrlIfArchiveIsEmpty(): void
    {
        $newsModelAdapter = $this->mockAdapter(['findPublishedDefaultByPid']);
        $newsModelAdapter
            ->expects($this->exactly(1))
            ->method('findPublishedDefaultByPid')
            ->willReturn(null)
        ;

        $adapters = [
            NewsArchiveModel::class => $this->getNewsArchiveModelAdapter(),
            PageModel::class => $this->getPageModelAdapter(),
            Controller::class => $this->getControllerAdapter(2),
            NewsModel::class => $newsModelAdapter,
            News::class => new Adapter(News::class),
        ];

        $sitemapEvent = new SitemapEvent($this->createSitemap(), new Request(), [1]);

        (new SitemapListener($this->mockContaoFramework($adapters)))($sitemapEvent);

        $this->assertSame(0, \count($sitemapEvent->getDocument()->firstChild->childNodes));
    }

    public function testDoesNotAddNewsUrlIfNewsIsNotInSitemap(): void
    {
        $article = $this->mockClassWithProperties(NewsModel::class, ['robots' => 'noindex,nofollow']);
        $articles = new Collection([$article], 'tl_news');

        $newsModelAdapter = $this->mockAdapter(['findPublishedDefaultByPid']);
        $newsModelAdapter
            ->expects($this->exactly(1))
            ->method('findPublishedDefaultByPid')
            ->willReturn($articles)
        ;

        $adapters = [
            NewsArchiveModel::class => $this->getNewsArchiveModelAdapter(),
            PageModel::class => $this->getPageModelAdapter(),
            Controller::class => $this->getControllerAdapter(2),
            NewsModel::class => $newsModelAdapter,
            News::class => new Adapter(News::class),
        ];

        $sitemapEvent = new SitemapEvent($this->createSitemap(), new Request(), [1]);

        (new SitemapListener($this->mockContaoFramework($adapters)))($sitemapEvent);

        $this->assertSame(0, \count($sitemapEvent->getDocument()->firstChild->childNodes));
    }

    private function createSitemap(): \DOMDocument
    {
        $sitemap = new \DOMDocument('1.0', 'UTF-8');
        $sitemap->formatOutput = true;
        $urlSet = $sitemap->createElementNS('https://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
        $sitemap->appendChild($urlSet);

        return $sitemap;
    }

    private function getNewsArchiveModelAdapter(array $newsArchiveProperties = []): Adapter
    {
        $archive = $this->mockClassWithProperties(NewsArchiveModel::class, $newsArchiveProperties + ['id' => 1, 'jumpTo' => 2, 'protected' => '']);
        $archives = new Collection([$archive], 'tl_news_archive');

        $newsArchiveModelAdapter = $this->mockAdapter(['findAll']);
        $newsArchiveModelAdapter
            ->expects($this->exactly(1))
            ->method('findAll')
            ->willReturn($archives)
        ;

        return $newsArchiveModelAdapter;
    }

    private function getPageModelAdapter(array $pageProperties = []): Adapter
    {
        $page = $this->mockClassWithProperties(PageModel::class, $pageProperties + ['rootId' => 1, 'protected' => '', 'robots' => 'index,follow']);
        $page
            ->method('loadDetails')
            ->willReturn($page)
        ;

        $pageModelAdapter = $this->mockAdapter(['findPublishedById']);
        $pageModelAdapter
            ->expects($this->exactly(1))
            ->method('findPublishedById')
            ->willReturn($page)
        ;

        return $pageModelAdapter;
    }

    private function getControllerAdapter(int $expects): Adapter
    {
        $controllerAdapter = $this->mockAdapter(['isVisibleElement']);
        $controllerAdapter
            ->expects($this->exactly($expects))
            ->method('isVisibleElement')
            ->willReturnCallback(
                static fn ($model): bool => !$model->protected
            )
        ;

        return $controllerAdapter;
    }
}
