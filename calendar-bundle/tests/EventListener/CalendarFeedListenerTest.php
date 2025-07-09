<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Tests\EventListener;

use Contao\CalendarBundle\Event\FetchEventsForFeedEvent;
use Contao\CalendarBundle\Event\TransformEventForFeedEvent;
use Contao\CalendarBundle\EventListener\CalendarFeedListener;
use Contao\CalendarBundle\Generator\CalendarEventsGenerator;
use Contao\CalendarEventsModel;
use Contao\CalendarModel;
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

class CalendarFeedListenerTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->resetStaticProperties([Files::class, System::class]);
    }

    #[DataProvider('featured')]
    public function testFetchesEventsFromCalendars(string $feedFeatured, bool|null $featuredOnly): void
    {
        $insertTags = $this->createMock(InsertTagParser::class);
        $imageFactory = $this->createMock(ImageFactoryInterface::class);
        $cacheTags = $this->createMock(CacheTagManager::class);
        $eventModel = $this->createMock(CalendarEventsModel::class);
        $normalCalendar = $this->mockClassWithProperties(CalendarModel::class, ['id' => 1, 'protected' => 0]);
        $protectedCalendar = $this->mockClassWithProperties(CalendarModel::class, ['id' => 2, 'protected' => 1]);

        $calendarAdapter = $this->mockAdapter(['findMultipleByIds']);
        $calendarAdapter
            ->expects($this->once())
            ->method('findMultipleByIds')
            ->with([1, 2])
            ->willReturn(new Collection([$normalCalendar, $protectedCalendar], 'tl_calendar'))
        ;

        $framework = $this->mockContaoFramework([CalendarModel::class => $calendarAdapter]);
        $feed = $this->createMock(Feed::class);
        $request = $this->createMock(Request::class);
        $urlGenerator = $this->createMock(ContentUrlGenerator::class);

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(false)
        ;

        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'eventCalendars' => serialize([1, 2]),
            'feedFeatured' => $feedFeatured,
            'maxFeedItems' => 0,
            'feedRecurrenceLimit' => 10,
        ]);

        $calendarEventsGenerator = $this->createMock(CalendarEventsGenerator::class);
        $calendarEventsGenerator
            ->expects($this->once())
            ->method('getAllEvents')
            ->with(
                $this->equalTo([1]),
                $this->anything(),
                $this->anything(),
                $featuredOnly,
                $this->equalTo(true),
                $this->equalTo(10),
            )
            ->willReturn([$eventModel])
        ;

        $event = new FetchEventsForFeedEvent($feed, $request, $pageModel);

        $listener = new CalendarFeedListener($calendarEventsGenerator, $framework, $imageFactory, $urlGenerator, $insertTags, $cacheTags, $authorizationChecker, $this->getTempDir(), 'UTF-8');
        $listener->onFetchEventsForFeed($event);

        $this->assertSame([$eventModel], $event->getEvents());
    }

    #[DataProvider('getFeedSource')]
    public function testTransformsEventsToFeedItems(string $feedSource, array $title, array $content): void
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
            'ptable' => 'tl_calendar_events',
        ]);

        $contentModel = $this->mockAdapter(['findPublishedByPidAndTable']);
        $contentModel
            ->method('findPublishedByPidAndTable')
            ->with(42, 'tl_calendar_events')
            ->willReturn(new Collection([$element], 'tl_calendar_events'))
        ;

        $eventModel = $this->mockClassWithProperties(CalendarEventsModel::class, [
            'id' => 42,
            'startTime' => 1656578758,
            'title' => $title[0],
            'teaser' => $content[0],
            'addImage' => true,
            'singleSRC' => 'binary_uuid',
            'addEnclosure' => 1,
            'enclosure' => serialize(['binary_uuid2']),
            'author' => 1,
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
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.org/news/example-title')
        ;

        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'feedSource' => $feedSource,
            'imgSize' => serialize([100, 100, 'crop']),
        ]);

        $request = $this->createMock(Request::class);
        $baseUrl = 'example.org';

        $eventData = $eventModel->row();
        $eventData['model'] = $eventModel;
        $eventData['begin'] = $eventModel->startTime;

        $event = new TransformEventForFeedEvent($eventData, $feed, $pageModel, $request, $baseUrl);

        $calendarEventsGenerator = $this->createMock(CalendarEventsGenerator::class);
        $calendarEventsGenerator
            ->expects($this->never())
            ->method('getAllEvents')
        ;

        $listener = new CalendarFeedListener($calendarEventsGenerator, $framework, $imageFactory, $urlGenerator, $insertTags, $cacheTags, $authorizationChecker, $this->getTempDir(), 'UTF-8');
        $listener->onTransformEventForFeed($event);

        $item = $event->getItem();

        $this->assertSame($title[1], $item->getTitle());
        $this->assertSame(1656578758, $item->getLastModified()->getTimestamp());
        $this->assertSame('https://example.org/news/example-title', $item->getLink());
        $this->assertSame('1cfcaaf3-79b2-515d-89aa-819773585f11', $item->getPublicId());
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
