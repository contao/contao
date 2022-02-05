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

        $adapters = [
            NewsArchiveModel::class => $this->mockConfiguredAdapter(['findAll' => null]),
        ];
        
        (new SitemapListener($this->mockContaoFramework($adapters)))($sitemapEvent);

        $this->assertSame(0, count($sitemapEvent->getDocument()->firstChild->childNodes));
    }

    public function testAddsNewsUrlToSitemap(): void
    {
        $archive = $this->mockClassWithProperties(NewsArchiveModel::class, ['id' => 1, 'jumpTo' => 2]);
        $archives = new Collection([$archive], 'tl_news_archive');

        $page = $this->mockClassWithProperties(PageModel::class, ['rootId' => 1, 'robots' => 'index,follow']);
        $page
            ->method('loadDetails')
            ->willReturn($page)
        ;

        $article = $this->mockClassWithProperties(NewsModel::class);
        $articles = new Collection([$article], 'tl_news');

        $newsAdapter = $this->mockAdapter(['generateNewsUrl']);
        $newsAdapter
            ->method('generateNewsUrl')
            ->willReturnCallback(
                static function (NewsModel $model, bool $addArchive, bool $absolute): string {
                    if ($absolute) {
                        return 'http://domain.tld/news/the-foobar-news.html';
                    }

                    return 'news/the-foobar-news.html';
                }
            )
        ;

        $adapters = [
            NewsArchiveModel::class => $this->mockConfiguredAdapter(['findAll' => $archives]),
            PageModel::class => $this->mockConfiguredAdapter(['findPublishedById' => $page]),
            Controller::class => $this->mockConfiguredAdapter(['isVisibleElement' => true]),
            NewsModel::class => $this->mockConfiguredAdapter(['findPublishedDefaultByPid' => $articles]),
            News::class => $newsAdapter,
        ];
        
        $sitemapEvent = new SitemapEvent($this->createSitemap(), new Request(), [1]);

        (new SitemapListener($this->mockContaoFramework($adapters)))($sitemapEvent);

        $sitemap = $sitemapEvent->getDocument();
        $this->assertSame('http://domain.tld/news/the-foobar-news.html', $sitemap->firstChild->firstChild->firstChild->nodeValue);
    }

    private function createSitemap(): \DOMDocument
    {
        $sitemap = new \DOMDocument('1.0', 'UTF-8');
        $sitemap->formatOutput = true;
        $urlSet = $sitemap->createElementNS('https://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
        $sitemap->appendChild($urlSet);
        return $sitemap;
    }
}
