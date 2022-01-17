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

class FeedItem
{
    private string $title;

    private ?string $description = null;

    private string $link;

    private string $guid;

    private \DateTime $lastUpdated;

    /**
     * @var array<Enclosure>
     */
    private array $enclosures = [];

    public static function create(): self
    {
        return new self();
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

    public function getGuid(): string
    {
        return $this->guid;
    }

    public function setGuid(string $guid): self
    {
        $this->guid = $guid;
        return $this;
    }

    public function getLastUpdated(): \DateTime
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(\DateTime $lastUpdated): self
    {
        $this->lastUpdated = $lastUpdated;
        return $this;
    }

    public function getEnclosures(): array
    {
        return $this->enclosures;
    }

    public function setEnclosures(array $enclosures): self
    {
        $this->enclosures = $enclosures;
        return $this;
    }

    public function addEnclosure(Enclosure $enclosure): self
    {
        $this->enclosures[] = $enclosure;
        return $this;
    }
}
