<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\EventListener;

use Contao\CalendarBundle\CalendarEventsGenerator;
use Contao\CalendarBundle\Event\FetchEventsForFeedEvent;
use Contao\CalendarBundle\Event\TransformEventForFeedEvent;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\Environment;
use Contao\File;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\UserModel;
use FeedIo\Feed\Item;
use FeedIo\Feed\Item\Author;
use FeedIo\Feed\Item\AuthorInterface;
use FeedIo\Feed\Item\Media;
use FeedIo\Feed\ItemInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
class CalendarFeedListener
{
    public function __construct(
        private readonly CalendarEventsGenerator $calendarEventsGenerator,
        private readonly ContaoFramework $framework,
        private readonly ImageFactoryInterface $imageFactory,
        private readonly ContentUrlGenerator $urlGenerator,
        private readonly InsertTagParser $insertTags,
        private readonly string $projectDir,
        private readonly CacheTagManager $cacheTags,
        private readonly string $charset,
    ) {
    }

    #[AsEventListener]
    public function onFetchEventsForFeed(FetchEventsForFeedEvent $event): void
    {
        $pageModel = $event->getPageModel();
        $calendars = StringUtil::deserialize($pageModel->eventCalendars, true);

        $featured = match ($pageModel->feedFeatured) {
            'featured' => true,
            'unfeatured' => false,
            default => null,
        };

        // TODO: feed mode to determine start and end
        $start = time();
        $end = strtotime('2032-01-01');

        $events = $this->calendarEventsGenerator->getAllEvents($calendars, $start, $end, $featured);
        $event->setEvents($events);
    }

    #[AsEventListener]
    public function onTransformEventForFeed(TransformEventForFeedEvent $systemEvent): void
    {
        $calendarEvent = $systemEvent->getEvent();

        $item = new Item();
        $item->setTitle(html_entity_decode($calendarEvent['title'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, $this->charset));
        $item->setLastModified((new \DateTime())->setTimestamp($calendarEvent['tstamp']));
        $item->setLink($this->urlGenerator->generate($calendarEvent['model'], [], UrlGeneratorInterface::ABSOLUTE_URL));
        $item->setContent($this->getContent($calendarEvent, $item, $systemEvent));
        $item->setPublicId($item->getLink());

        if ($author = $this->getAuthor($calendarEvent)) {
            $item->setAuthor($author);
        }

        $enclosures = $this->getEnclosures($calendarEvent, $systemEvent);

        foreach ($enclosures as $enclosure) {
            $item->addMedia($enclosure);
        }

        $systemEvent->setItem($item);
    }

    private function getContent(array $calendarEvent, ItemInterface $item, TransformEventForFeedEvent $systemEvent): string
    {
        $pageModel = $systemEvent->getPageModel();

        $environment = $this->framework->getAdapter(Environment::class);
        $controller = $this->framework->getAdapter(Controller::class);
        $contentModel = $this->framework->getAdapter(ContentModel::class);

        $description = $event['teaser'] ?? '';

        // Prepare the description
        if ('source_text' === $pageModel->feedSource) {
            $elements = $contentModel->findPublishedByPidAndTable($calendarEvent['id'], 'tl_calendar_events');

            if (null !== $elements) {
                $description = '';

                // Overwrite the request (see #7756)
                $environment->set('request', $item->getLink());

                foreach ($elements as $element) {
                    $description .= $controller->getContentElement($element);

                    $this->cacheTags->tagWithModelInstance($element);
                }

                $environment->set('request', $systemEvent->getRequest()->getUri());
            }
        }

        $description = $this->insertTags->replaceInline($description);

        return $controller->convertRelativeUrls($description, $item->getLink());
    }

    private function getAuthor(array $event): AuthorInterface|null
    {
        if ($authorModel = $this->framework->getAdapter(UserModel::class)->findById($event['author'])) {
            return (new Author())->setName($authorModel->name);
        }

        return null;
    }

    private function getEnclosures(array $calendarEvent, TransformEventForFeedEvent $systemEvent): array
    {
        $uuids = [];

        if ($calendarEvent['addImage'] && $calendarEvent['singleSRC']) {
            $uuids[] = $calendarEvent['singleSRC'];
        }

        if ($calendarEvent['addEnclosure']) {
            $uuids = [...$uuids, ...StringUtil::deserialize($calendarEvent['enclosure'], true)];
        }

        if (!$uuids) {
            return [];
        }

        $filesAdapter = $this->framework->getAdapter(FilesModel::class);
        $fileModels = $filesAdapter->findMultipleByUuids($uuids);

        if (null === $fileModels) {
            return [];
        }

        $baseUrl = $systemEvent->getBaseUrl();
        $pageModel = $systemEvent->getPageModel();
        $size = StringUtil::deserialize($pageModel->imgSize, true);
        $enclosures = [];

        foreach ($fileModels as $fileModel) {
            $file = new File($fileModel->path);

            if (!$file->exists()) {
                continue;
            }

            $fileUrl = $baseUrl.'/'.$file->path;
            $fileSize = $file->filesize;

            if ($size && $file->isImage) {
                $image = $this->imageFactory->create(Path::join($this->projectDir, $file->path), $size);
                $fileUrl = $baseUrl.'/'.$image->getUrl($this->projectDir);
                $file = new File(Path::makeRelative($image->getPath(), $this->projectDir));
                $fileSize = $file->exists() ? $file->filesize : null;
            }

            $media = (new Media())->setUrl($fileUrl)->setType($file->mime);

            if ($fileSize) {
                $media->setLength($fileSize);
            }

            $enclosures[] = $media;
        }

        return $enclosures;
    }
}
