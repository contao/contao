<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Event;

use Contao\PageModel;
use FeedIo\FeedInterface;
use Symfony\Component\HttpFoundation\Request;

class FetchEventsForFeedEvent
{
    /**
     * @var list<array<string, mixed>>|null
     */
    private array|null $events = null;

    public function __construct(
        private readonly FeedInterface $feed,
        private readonly Request $request,
        private readonly PageModel $pageModel,
    ) {
    }

    public function getFeed(): FeedInterface
    {
        return $this->feed;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getPageModel(): PageModel
    {
        return $this->pageModel;
    }

    public function getEvents(): array|null
    {
        return $this->events;
    }

    /**
     * @param list<array<string, mixed>> $events
     */
    public function setEvents(array $events): void
    {
        $this->events = $events;
    }

    public function addEvents(array $events): void
    {
        $this->events = [...$this->events, ...$events];
    }
}
