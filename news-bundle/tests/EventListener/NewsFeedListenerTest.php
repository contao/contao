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
use Contao\NewsArchiveModel;
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
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class NewsFeedListenerTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->resetStaticProperties([Files::class, System::class]);
    }

    #[DataProvider('featured')]
    public function testFetchesArticlesFromArchives(string $feedFeatured, bool|null $featuredOnly): void
    {
        $insertTags = $this->createStub(InsertTagParser::class);
        $imageFactory = $this->createStub(ImageFactoryInterface::class);
        $cacheTags = $this->createStub(CacheTagManager::class);
        $newsModel = $this->createStub(NewsModel::class);
        $normalArchive = $this->createClassWithPropertiesStub(NewsArchiveModel::class, ['id' => 1, 'protected' => 0]);
        $protectedArchive = $this->createClassWithPropertiesStub(NewsArchiveModel::class, ['id' => 2, 'protected' => 1]);

        $collection = $this->createMock(Collection::class);
        $collection
            ->expects($this->once())
            ->method('getModels')
            ->willReturn([$newsModel])
        ;

        $newsAdapter = $this->createAdapterMock(['findPublishedByPids']);
        $newsAdapter
            ->expects($this->once())
            ->method('findPublishedByPids')
            ->with([1], $featuredOnly, 0)
            ->willReturn($collection)
        ;

        $newsArchiveAdapter = $this->createAdapterMock(['findMultipleByIds']);
        $newsArchiveAdapter
            ->expects($this->once())
            ->method('findMultipleByIds')
            ->with([1, 2])
            ->willReturn(new Collection([$normalArchive, $protectedArchive], 'tl_news_archive'))
        ;

        $framework = $this->createContaoFrameworkStub([NewsModel::class => $newsAdapter, NewsArchiveModel::class => $newsArchiveAdapter]);
        $feed = $this->createStub(Feed::class);
        $request = $this->createStub(Request::class);
        $urlGenerator = $this->createStub(ContentUrlGenerator::class);

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(false)
        ;

        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, [
            'type' => 'news_feed',
            'newsArchives' => serialize([1, 2]),
            'feedFeatured' => $feedFeatured,
            'maxFeedItems' => 0,
        ]);

        $event = new FetchArticlesForFeedEvent($feed, $request, $pageModel);

        $listener = new NewsFeedListener($framework, $imageFactory, $urlGenerator, $insertTags, $this->getTempDir(), $cacheTags, 'UTF-8', $authorizationChecker);
        $listener->onFetchArticlesForFeed($event);

        $this->assertSame([$newsModel], $event->getArticles());
    }

    #[DataProvider('getFeedSource')]
    public function testTransformsArticlesToFeedItems(string $feedSource, string $headline, string $teaser, string $content, string $expectedFeedTitle, string $expectedFeedContent): void
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

        $image = $this->createStub(ImageInterface::class);
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

        $imageFactory = $this->createStub(ImageFactoryInterface::class);
        $imageFactory
            ->method('create')
            ->willReturn($image)
        ;

        $isDetail = 'source_text' === $feedSource;

        $insertTags = $this->createMock(InsertTagParser::class);
        $insertTags
            ->expects($this->once())
            ->method('replaceInline')
            ->willReturn($isDetail ? $content : $teaser)
        ;

        $element = $this->createStub(ContentModel::class);

        $contentModel = $this->createAdapterMock(['findPublishedByPidAndTable']);
        $contentModel
            ->expects($this->exactly($isDetail ? 1 : 0))
            ->method('findPublishedByPidAndTable')
            ->with(42, 'tl_news')
            ->willReturn(new Collection([$element], 'tl_news'))
        ;

        $article = $this->createClassWithPropertiesStub(NewsModel::class, [
            'id' => 42,
            'date' => 1656578758,
            'headline' => $headline,
            'teaser' => $teaser,
            'addImage' => true,
            'singleSRC' => 'binary_uuid',
            'addEnclosure' => serialize(['binary_uuid2']),
        ]);

        $environment = $this->createAdapterStub(['set', 'get']);

        $controller = $this->createAdapterMock(['getContentElement', 'convertRelativeUrls']);
        $controller
            ->expects($this->exactly($isDetail ? 1 : 0))
            ->method('getContentElement')
            ->with($element)
            ->willReturn($content)
        ;

        $controller
            ->expects($this->once())
            ->method('convertRelativeUrls')
            ->with($isDetail ? $content : $teaser)
            ->willReturn($isDetail ? $content : $teaser)
        ;

        $filesModel = $this->createAdapterMock(['findMultipleByUuids']);
        $filesModel
            ->expects($this->once())
            ->method('findMultipleByUuids')
            ->willReturn(
                new Collection(
                    [
                        $this->createClassWithPropertiesStub(FilesModel::class, ['path' => 'files/foo.jpg']),
                        $this->createClassWithPropertiesStub(FilesModel::class, ['path' => 'files/bar.jpg']),
                    ],
                    'tl_files',
                ),
            )
        ;

        $userModel = $this->createClassWithPropertiesStub(UserModel::class, ['name' => 'Jane Doe']);

        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());
        System::setContainer($container);

        $framework = $this->createContaoFrameworkStub([
            Environment::class => $environment,
            Controller::class => $controller,
            ContentModel::class => $contentModel,
            FilesModel::class => $filesModel,
            UserModel::class => $this->createConfiguredAdapterStub(['findById' => $userModel]),
        ]);

        $framework->setContainer($container);

        $feed = $this->createStub(Feed::class);
        $cacheTags = $this->createStub(CacheTagManager::class);
        $authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($article, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.org/news/example-title')
        ;

        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, [
            'type' => 'news_feed',
            'feedSource' => $feedSource,
            'imgSize' => serialize([100, 100, 'crop']),
        ]);

        $request = $this->createStub(Request::class);
        $baseUrl = 'example.org';
        $event = new TransformArticleForFeedEvent($article, $feed, $pageModel, $request, $baseUrl);

        $listener = new NewsFeedListener($framework, $imageFactory, $urlGenerator, $insertTags, $this->getTempDir(), $cacheTags, 'UTF-8', $authorizationChecker);
        $listener->onTransformArticleForFeed($event);

        $item = $event->getItem();

        $this->assertSame($expectedFeedTitle, $item->getTitle());
        $this->assertSame(1656578758, $item->getLastModified()->getTimestamp());
        $this->assertSame('https://example.org/news/example-title', $item->getLink());
        $this->assertSame('https://example.org/news/example-title', $item->getPublicId());
        $this->assertSame($expectedFeedContent, $item->getContent());
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
            'Example title &#40;Episode 1&#41;',
            'Example teaser &#40;Episode 1&#41;',
            'Example content &#40;Episode 1&#41;',
            'Example title (Episode 1)',
            'Example teaser &#40;Episode 1&#41;',
        ];

        yield 'Text' => [
            'source_text',
            'Example title &#40;Episode 1&#41;',
            'Example teaser &#40;Episode 1&#41;',
            'Example content &#40;Episode 1&#41;',
            'Example title (Episode 1)',
            'Example content &#40;Episode 1&#41;',
        ];
    }
}
