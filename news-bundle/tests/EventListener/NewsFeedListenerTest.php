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
use Contao\Files;
use Contao\FilesModel;
use Contao\Image\ImageInterface;
use Contao\Model\Collection;
use Contao\News;
use Contao\NewsBundle\Event\FetchArticlesForFeedEvent;
use Contao\NewsBundle\Event\TransformArticleForFeedEvent;
use Contao\NewsBundle\EventListener\NewsFeedListener;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Contao\UserModel;
use FeedIo\Feed;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;

class NewsFeedListenerTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->resetStaticProperties([Files::class, System::class]);
    }

    /**
     * @dataProvider featured
     */
    public function testFetchesArticlesFromArchives(string $feedFeatured, bool|null $featuredOnly): void
    {
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

        $framework = $this->mockContaoFramework([NewsModel::class => $newsModel]);
        $feed = $this->createMock(Feed::class);
        $request = $this->createMock(Request::class);

        $pageModel = $this->mockClassWithProperties(
            PageModel::class,
            [
                'newsArchives' => serialize([1]),
                'feedFeatured' => $feedFeatured,
                'maxFeedItems' => 0,
            ]
        );

        $event = new FetchArticlesForFeedEvent($feed, $request, $pageModel);

        $listener = new NewsFeedListener($framework, $imageFactory, $insertTags, $this->getTempDir(), $cacheTags, 'UTF-8');
        $listener->onFetchArticlesForFeed($event);

        $this->assertSame($collection, $event->getArticles());
    }

    /**
     * @dataProvider getFeedSource
     */
    public function testTransformsArticlesToFeedItems(string $feedSource, array $headline, array $content): void
    {
        $imageDir = Path::join($this->getTempDir(), 'files');

        $fs = new Filesystem();
        $fs->mkdir($imageDir);

        $imagine = new Imagine();

        foreach (['foo.jpg', 'bar.jpg'] as $filename) {
            $imagine
                ->create(new Box(100, 100))
                ->save(Path::join($imageDir, $filename))
            ;
        }

        $image = $this->createMock(ImageInterface::class);
        $image
            ->method('getUrl')
            ->willReturnOnConsecutiveCalls(
                'files/foo.jpg',
                'files/bar.jpg',
            )
        ;

        $image
            ->method('getPath')
            ->willReturnOnConsecutiveCalls(
                $imageDir.'/foo.jpg',
                $imageDir.'/bar.jpg',
            )
        ;

        $image
            ->method('getUrl')
            ->willReturnOnConsecutiveCalls(
                'files/foo.jpg',
                'files/bar.jpg',
            )
        ;

        $imageFactory = $this->createMock(ImageFactoryInterface::class);
        $imageFactory
            ->method('create')
            ->willReturn($image)
        ;

        $insertTags = $this->createMock(InsertTagParser::class);
        $insertTags
            ->expects($this->once())
            ->method('replaceInline')
            ->willReturn($content[0])
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
            'headline' => $headline[0],
            'teaser' => $content[0],
            'singleSRC' => 'binary_uuid',
            'addEnclosure' => serialize(['binary_uuid2']),
        ]);

        $article
            ->expects($this->once())
            ->method('getRelated')
            ->with('author')
            ->willReturn($this->mockClassWithProperties(UserModel::class, ['name' => 'Jane Doe']))
        ;

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
            ->willReturn($content[0])
        ;

        $filesModel = $this->mockAdapter(['findMultipleByUuids']);
        $filesModel
            ->expects($this->once())
            ->method('findMultipleByUuids')
            ->willReturn(
                new Collection(
                    [
                        $this->mockClassWithProperties(FilesModel::class, ['path' => 'files/foo.jpg']),
                        $this->mockClassWithProperties(FilesModel::class, ['path' => 'files/bar.jpg']),
                    ],
                    'tl_files'
                )
            )
        ;

        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());
        System::setContainer($container);

        $framework = $this->mockContaoFramework([
            News::class => $news,
            Environment::class => $environment,
            Controller::class => $controller,
            ContentModel::class => $contentModel,
            FilesModel::class => $filesModel,
        ]);

        $framework->setContainer($container);

        $feed = $this->createMock(Feed::class);
        $cacheTags = $this->createMock(EntityCacheTags::class);

        $pageModel = $this->mockClassWithProperties(
            PageModel::class,
            [
                'feedSource' => $feedSource,
                'imgSize' => serialize([100, 100, 'crop']),
            ]
        );

        $request = $this->createMock(Request::class);
        $baseUrl = 'example.org';
        $event = new TransformArticleForFeedEvent($article, $feed, $pageModel, $request, $baseUrl);

        $listener = new NewsFeedListener($framework, $imageFactory, $insertTags, $this->getTempDir(), $cacheTags, 'UTF-8');
        $listener->onTransformArticleForFeed($event);

        $item = $event->getItem();

        $this->assertSame($headline[1], $item->getTitle());
        $this->assertSame(1656578758, $item->getLastModified()->getTimestamp());
        $this->assertSame('https://example.org/news/example-title', $item->getLink());
        $this->assertSame('https://example.org/news/example-title', $item->getPublicId());
        $this->assertSame($content[1], $item->getContent());
        $this->assertSame('Jane Doe', $item->getAuthor()->getName());
        $this->assertCount(2, $item->getMedias());

        $fs->remove($imageDir);
    }

    public function featured(): \Generator
    {
        yield 'All items' => ['all_items', null];
        yield 'Only featured' => ['featured', true];
        yield 'Only unfeatured items' => ['unfeatured', false];
    }

    public function getFeedSource(): \Generator
    {
        yield 'Teaser' => [
            'source_teaser',
            ['Example title &#40;Episode 1&#41;', 'Example title (Episode 1)'],
            ['Example teaser &#40;Episode 1&#41;', 'Example teaser &#40;Episode 1&#41;'],
        ];

        yield 'Text' => [
            'source_text',
            ['Example title &#40;Episode 1&#41;', 'Example title (Episode 1)'],
            ['Example content &#40;Episode 1&#41;', 'Example content &#40;Episode 1&#41;'],
        ];
    }
}
