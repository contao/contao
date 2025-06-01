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
use FeedIo\Feed\ItemInterface;
use FeedIo\FeedInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class TransformEventForFeedEvent extends Event
{
    private ItemInterface|null $item = null;

    public function __construct(
        /** @var array<string, mixed> $event */
        private readonly array $event,
        private readonly FeedInterface $feed,
        private readonly PageModel $pageModel,
        private readonly Request $request,
        private readonly string $baseUrl,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getEvent(): array
    {
        return $this->event;
    }

    public function getFeed(): FeedInterface
    {
        return $this->feed;
    }

    public function getPageModel(): PageModel
    {
        return $this->pageModel;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getItem(): ItemInterface|null
    {
        return $this->item;
    }

    public function setItem(ItemInterface $item): void
    {
        $this->item = $item;
    }
}
