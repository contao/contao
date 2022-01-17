<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Feed;

use DateTime;

class Feed
{
    private string $alias;

    private string $title;

    private ?string $description = null;

    private string $link = '';

    private string $language = '';

    private DateTime $lastUpdated;

    /**
     * @var FeedItem[]
     */
    private array $items = [];

    public static function create(string $alias): self
    {
        return new self($alias);
    }

    public function __construct(string $alias)
    {
        $this->alias = $alias;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): void
    {
        $this->alias = $alias;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getLastUpdated(): DateTime
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(DateTime $lastUpdated): self
    {
        $this->lastUpdated = $lastUpdated;

        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function addItem(FeedItem $item): self
    {
        $this->items[] = $item;

        return $this;
    }
}
