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

use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\Environment;
use Contao\Model\Collection;
use Contao\News;
use Contao\NewsBundle\Event\FetchArticlesForFeedEvent;
use Contao\NewsBundle\Event\TransformArticleForFeedEvent;
use Contao\NewsBundle\EventListener\NewsFeedListener;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use FeedIo\Feed;
use Symfony\Component\HttpFoundation\Request;

class NewsFeedListenerTest extends ContaoTestCase
{
    /**
     * @dataProvider featured
     */
    public function testFetchesArticlesFromArchives(string $feedFeatured, ?bool $featuredOnly): void
    {
        $projectDir = '/project';
        $collection = [$this->mockClassWithProperties(NewsModel::class)];
        $insertTags = $this->createMock(InsertTagParser::class);
        $imageFactory = $this->createMock(ImageFactoryInterface::class);
        $cacheTags = $this->createMock(EntityCacheTags::class);

        $newsModel = $this->mockAdapter(['findPublishedByPids']);
        $newsModel
            ->expects($this->once())
            ->method('findPublishedByPids')
            ->with([1], $featuredOnly, 0, 0)
            ->willReturn($collection)
        ;

        $listener = new NewsFeedListener($this->mockContaoFramework([NewsModel::class => $newsModel]), $imageFactory, $insertTags, $projectDir, $cacheTags);

        $feed = $this->createMock(Feed::class);
        $request = $this->createMock(Request::class);
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'newsArchives' => serialize([1]),
            'feedFeatured' => $feedFeatured,
            'maxFeedItems' => 0,
        ]);

        $event = new FetchArticlesForFeedEvent($feed, $request, $pageModel);

        $listener->onFetchArticlesForFeed($event);
        $this->assertSame($collection, $event->getArticles());
    }

    /**
     * @dataProvider getFeedSource
     */
    public function testTransformsArticlesToFeedItems(string $feedSource, string $expecedContent): void
    {
        $projectDir = '/project';
        $imageFactory = $this->createMock(ImageFactoryInterface::class);
        $cacheTags = $this->createMock(EntityCacheTags::class);

        $insertTags = $this->createMock(InsertTagParser::class);
        $insertTags
            ->expects($this->once())
            ->method('replaceInline')
            ->willReturn($expecedContent)
        ;

        $element = $this->mockClassWithProperties(ContentModel::class, [
            'pid' => 42,
            'ptable' => 'tl_news',
        ]);

        $contentModel = $this->mockAdapter(['findPublishedByPidAndTable']);
        $contentModel
            ->method('findPublishedByPidAndTable')
            ->with(42, 'tl_news')
            ->willReturn(new Collection([$element], 'tl_news'))
        ;

        $article = $this->mockClassWithProperties(NewsModel::class, [
            'id' => 42,
            'date' => 1656578758,
            'headline' => 'Example title',
            'teaser' => 'Example teaser',
        ]);

        $news = $this->mockAdapter(['generateNewsUrl']);
        $news
            ->expects($this->once())
            ->method('generateNewsUrl')
            ->with($article, false, true)
            ->willReturn('https://example.org/news/example-title')
        ;

        $environment = $this->mockAdapter(['set', 'get']);

        $controller = $this->mockAdapter(['getContentElement', 'convertRelativeUrls']);
        $controller
            ->method('convertRelativeUrls')
            ->willReturn($expecedContent)
        ;

        $framework = $this->mockContaoFramework([
            News::class => $news,
            Environment::class => $environment,
            Controller::class => $controller,
            ContentModel::class => $contentModel,
        ]);

        $listener = new NewsFeedListener($framework, $imageFactory, $insertTags, $projectDir, $cacheTags);

        $feed = $this->createMock(Feed::class);
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'feedSource' => $feedSource,
        ]);
        $request = $this->createMock(Request::class);
        $baseUrl = 'example.org';

        $event = new TransformArticleForFeedEvent($article, $feed, $pageModel, $request, $baseUrl);

        $listener->onTransformArticleForFeed($event);

        $item = $event->getItem();

        $this->assertSame($item->getTitle(), 'Example title');
        $this->assertSame($item->getLastModified()->getTimestamp(), 1656578758);
        $this->assertSame($item->getLink(), 'https://example.org/news/example-title');
        $this->assertSame($item->getPublicId(), 'https://example.org/news/example-title');
        $this->assertSame($item->getContent(), $expecedContent);
    }

    public function featured(): \Generator
    {
        yield 'All items' => ['all_items', null];
        yield 'Only featured' => ['featured', true];
        yield 'Only unfeatured items' => ['unfeatured', false];
    }

    public function getFeedSource(): \Generator
    {
        yield 'Teaser only' => ['source_teaser', 'Example teaser'];
        yield 'Text' => ['source_text', 'Example content'];
    }
}
