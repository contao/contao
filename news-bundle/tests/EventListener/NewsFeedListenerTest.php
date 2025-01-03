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
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\Environment;
use Contao\Files;
use Contao\FilesModel;
use Contao\Image\ImageInterface;
use Contao\Model\Collection;
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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
        $insertTags = $this->createMock(InsertTagParser::class);
        $imageFactory = $this->createMock(ImageFactoryInterface::class);
        $cacheTags = $this->createMock(CacheTagManager::class);
        $newsModel = $this->createMock(NewsModel::class);

        $collection = $this->createMock(Collection::class);
        $collection
            ->expects($this->once())
            ->method('getModels')
            ->willReturn([$newsModel])
        ;

        $newsAdapter = $this->mockAdapter(['findPublishedByPids']);
        $newsAdapter
            ->expects($this->once())
            ->method('findPublishedByPids')
            ->with([1], $featuredOnly, 0)
            ->willReturn($collection)
        ;

        $framework = $this->mockContaoFramework([NewsModel::class => $newsAdapter]);
        $feed = $this->createMock(Feed::class);
        $request = $this->createMock(Request::class);
        $urlGenerator = $this->createMock(ContentUrlGenerator::class);

        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'newsArchives' => serialize([1]),
            'feedFeatured' => $feedFeatured,
            'maxFeedItems' => 0,
        ]);

        $event = new FetchArticlesForFeedEvent($feed, $request, $pageModel);

        $listener = new NewsFeedListener($framework, $imageFactory, $urlGenerator, $insertTags, $this->getTempDir(), $cacheTags, 'UTF-8');
        $listener->onFetchArticlesForFeed($event);

        $this->assertSame([$newsModel], $event->getArticles());
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
            'addImage' => true,
            'singleSRC' => 'binary_uuid',
            'addEnclosure' => serialize(['binary_uuid2']),
        ]);

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
                    'tl_files',
                ),
            )
        ;

        $userModel = $this->mockClassWithProperties(UserModel::class, ['name' => 'Jane Doe']);

        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());
        System::setContainer($container);

        $framework = $this->mockContaoFramework([
            Environment::class => $environment,
            Controller::class => $controller,
            ContentModel::class => $contentModel,
            FilesModel::class => $filesModel,
            UserModel::class => $this->mockConfiguredAdapter(['findById' => $userModel]),
        ]);

        $framework->setContainer($container);

        $feed = $this->createMock(Feed::class);
        $cacheTags = $this->createMock(CacheTagManager::class);

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($article, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.org/news/example-title')
        ;

        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'feedSource' => $feedSource,
            'imgSize' => serialize([100, 100, 'crop']),
        ]);

        $request = $this->createMock(Request::class);
        $baseUrl = 'example.org';
        $event = new TransformArticleForFeedEvent($article, $feed, $pageModel, $request, $baseUrl);

        $listener = new NewsFeedListener($framework, $imageFactory, $urlGenerator, $insertTags, $this->getTempDir(), $cacheTags, 'UTF-8');
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

    public static function featured(): iterable
    {
        yield 'All items' => ['all_items', null];
        yield 'Only featured' => ['featured', true];
        yield 'Only unfeatured items' => ['unfeatured', false];
    }

    public static function getFeedSource(): iterable
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
