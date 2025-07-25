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

use Contao\CalendarBundle\Event\FetchEventsForFeedEvent;
use Contao\CalendarBundle\Event\TransformEventForFeedEvent;
use Contao\CalendarBundle\Generator\CalendarEventsGenerator;
use Contao\CalendarModel;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Environment;
use Contao\File;
use Contao\FilesModel;
use Contao\ModuleEventlist;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\UserModel;
use FeedIo\Feed\Item;
use FeedIo\Feed\Item\Author;
use FeedIo\Feed\Item\AuthorInterface;
use FeedIo\Feed\Item\Media;
use FeedIo\Feed\ItemInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Uid\Uuid;

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
        private readonly CacheTagManager $cacheTags,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly string $projectDir,
        private readonly string $charset,
    ) {
    }

    #[AsEventListener]
    public function onFetchEventsForFeed(FetchEventsForFeedEvent $systemEvent): void
    {
        $pageModel = $systemEvent->getPageModel();
        $calendars = $this->sortOutProtected(StringUtil::deserialize($pageModel->eventCalendars, true));

        $featured = match ($pageModel->feedFeatured) {
            'featured' => true,
            'unfeatured' => false,
            default => null,
        };

        // Create a faux Module instance, so that the getAllEvents hook can be executed
        $moduleModel = $this->framework->createInstance(ModuleModel::class);
        $moduleModel->type = 'eventlist';
        $moduleModel->cal_calendar = $calendars;
        $moduleModel->cal_noSpan = true;
        $moduleModel->cal_format = 'next_all';
        $moduleModel->cal_order = 'ascending';
        $moduleModel->cal_featured = $pageModel->feedFeatured;
        $moduleModel->preventSaving(false);

        $module = $this->framework->createInstance(ModuleEventlist::class, [$moduleModel]);

        $calendarEvents = $this->calendarEventsGenerator->getAllEvents($calendars, new \DateTime(), new \DateTime('9999-12-31 23:59:59'), $featured, true, (int) $pageModel->feedRecurrenceLimit, $module);

        $systemEvent->setEvents($calendarEvents);
    }

    #[AsEventListener]
    public function onTransformEventForFeed(TransformEventForFeedEvent $systemEvent): void
    {
        $calendarEvent = $systemEvent->getEvent();

        if ($calendarEvent['time']) {
            $title = \sprintf('%s (%s) %s', $calendarEvent['date'], $calendarEvent['time'], $calendarEvent['title']);
        } else {
            $title = \sprintf('%s %s', $calendarEvent['date'], $calendarEvent['title']);
        }

        $item = new Item();
        $item->setTitle(html_entity_decode($title, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, $this->charset));
        $item->setLastModified((new \DateTime())->setTimestamp($calendarEvent['begin']));

        try {
            $item->setLink($this->urlGenerator->generate($calendarEvent['model'], [], UrlGeneratorInterface::ABSOLUTE_URL));
        } catch (ExceptionInterface) {
            // noop
        }

        $item->setContent($this->getContent($calendarEvent, $item, $systemEvent));

        // Create a unique ID due to recurrences
        $namespace = Uuid::fromString(Uuid::NAMESPACE_OID);
        $item->setPublicId(Uuid::v5($namespace, $item->getLink().'#'.$calendarEvent['begin'])->toRfc4122());

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

        $description = $calendarEvent['teaser'] ?? '';

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

    private function getAuthor(array $calendarEvent): AuthorInterface|null
    {
        if ($authorModel = $this->framework->getAdapter(UserModel::class)->findById($calendarEvent['author'])) {
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

    /**
     * @param list<int> $calendarIds
     *
     * @return list<int>
     */
    private function sortOutProtected(array $calendarIds): array
    {
        $calendarModel = $this->framework->getAdapter(CalendarModel::class);
        $allowedIds = [];

        foreach ($calendarModel->findMultipleByIds($calendarIds) ?? [] as $calendar) {
            if ($calendar->protected && !$this->authorizationChecker->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, $calendar->groups)) {
                continue;
            }

            $allowedIds[] = (int) $calendar->id;
        }

        return $allowedIds;
    }
}
