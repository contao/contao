<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Event;

use Contao\NewsModel;
use Contao\PageModel;
use FeedIo\Feed\ItemInterface;
use FeedIo\FeedInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class TransformArticleForFeedEvent extends Event
{
    private ItemInterface|null $item = null;

    public function __construct(private readonly NewsModel $article, private readonly FeedInterface $feed, private readonly PageModel $pageModel, private readonly Request $request, private readonly string $baseUrl)
    {
    }

    public function getArticle(): NewsModel
    {
        return $this->article;
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
