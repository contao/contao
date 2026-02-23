<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Controller\Page;

use Contao\CalendarBundle\Event\FetchEventsForFeedEvent;
use Contao\CalendarBundle\Event\TransformEventForFeedEvent;
use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Controller\Page\AbstractFeedPageController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsPage;
use Contao\PageModel;
use Contao\StringUtil;
use FeedIo\Feed;
use FeedIo\Specification;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsPage(path: '', contentComposition: false)]
class CalendarFeedController extends AbstractFeedPageController
{
    final public const TYPE = 'calendar_feed';

    public function __construct(
        private readonly ContaoContext $contaoContext,
        private readonly Specification $specification,
        private readonly string $charset,
    ) {
    }

    public function __invoke(Request $request, PageModel $pageModel): Response
    {
        $this->initializeContaoFramework();

        $staticUrl = $this->contaoContext->getBasePath();
        $baseUrl = $staticUrl ?: $request->getSchemeAndHttpHost();

        $feed = new Feed();
        $feed->setTitle(html_entity_decode($pageModel->title, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, $this->charset));
        $feed->setDescription(html_entity_decode($pageModel->feedDescription ?? '', ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, $this->charset));
        $feed->setLanguage($pageModel->language);

        $event = new FetchEventsForFeedEvent($feed, $request, $pageModel);

        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch($event);

        foreach ($event->getEvents() ?? [] as $v) {
            foreach ($v as $vv) {
                foreach ($vv as $event) {
                    $systemEvent = new TransformEventForFeedEvent($event, $feed, $pageModel, $request, $baseUrl);
                    $dispatcher->dispatch($systemEvent);

                    if (!$item = $systemEvent->getItem()) {
                        continue;
                    }

                    $feed->add($item);

                    $this->tagResponse($event['model'] ?? null);
                }
            }
        }

        $formatter = $this->specification->getStandard($pageModel->feedFormat)->getFormatter();

        $response = new Response($formatter->toString($feed));
        $response->headers->set('Content-Type', self::$contentTypes[$pageModel->feedFormat]);

        $this->setCacheHeaders($response, $pageModel);

        // Always add the response tags for the selected calendars
        $archiveIds = StringUtil::deserialize($pageModel->eventCalendars, true);
        $this->tagResponse(array_map(static fn ($id): string => 'contao.db.tl_calendar.'.$id, $archiveIds));

        return $response;
    }
}
