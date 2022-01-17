<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event;

use Contao\CoreBundle\Feed\Feed;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class FeedEvent extends Event
{
    private string $alias;

    private ?Feed $feed = null;

    private ?string $type;

    private Request $request;

    public function __construct(string $alias, Request $request)
    {
        $this->alias = $alias;
        $this->request = $request;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getFeed(): ?Feed
    {
        return $this->feed;
    }

    public function setFeed(Feed $feed): void
    {
        $this->feed = $feed;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
