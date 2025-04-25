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
use FeedIo\FeedInterface;
use Symfony\Component\HttpFoundation\Request;

class FetchArticlesForFeedEvent
{
    /**
     * @var array<NewsModel>|null
     */
    private array|null $articles = null;

    private \DateTimeInterface|null $expires = null;

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

    public function getArticles(): array|null
    {
        return $this->articles;
    }

    public function getExpires(): \DateTimeInterface|null
    {
        return $this->expires;
    }

    /**
     * @param array<NewsModel> $articles
     */
    public function setArticles(array $articles): void
    {
        $this->articles = $articles;
    }

    public function addArticles(array $articles): void
    {
        $this->articles = [...$this->articles, ...$articles];
    }

    public function setExpires(\DateTimeInterface $expires, bool $override = false): void
    {
        if (!$this->expires || $override || $this->expires < $expires) {
            $this->expires = $expires;
        }
    }
}
