<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext\JsonLd;

use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Spatie\SchemaOrg\BaseType;

class ContaoPageSchema extends BaseType
{
    /**
     * ContaoPageSchema constructor.
     *
     * @param array<int> $groups
     */
    public function __construct(string $title, int $pageId, bool $noSearch, bool $protected, array $groups, bool $fePreview)
    {
        $this->setTitle($title);
        $this->setPageId($pageId);
        $this->setNoSearch($noSearch);
        $this->setProtected($protected);
        $this->setGroups($groups);
        $this->setFePreview($fePreview);
    }

    #[\Override]
    public function getContext(): string
    {
        return 'https://schema.contao.org/';
    }

    #[\Override]
    public function getType(): string
    {
        return 'Page';
    }

    public function getTitle(): string
    {
        return $this->properties['title'];
    }

    public function setTitle(string $title): self
    {
        $this->properties['title'] = $title;

        return $this;
    }

    public function getPageId(): int
    {
        return $this->properties['pageId'];
    }

    public function setPageId(int $pageId): self
    {
        $this->properties['pageId'] = $pageId;

        return $this;
    }

    public function isNoSearch(): bool
    {
        return $this->properties['noSearch'];
    }

    public function setNoSearch(bool $noSearch): self
    {
        $this->properties['noSearch'] = $noSearch;

        return $this;
    }

    public function isProtected(): bool
    {
        return $this->properties['protected'];
    }

    public function setProtected(bool $protected): self
    {
        $this->properties['protected'] = $protected;

        return $this;
    }

    /**
     * @return array<int>
     */
    public function getGroups(): array
    {
        return $this->properties['groups'];
    }

    /**
     * @param array<int> $groups
     */
    public function setGroups(array $groups): self
    {
        $this->properties['groups'] = array_map('intval', $groups);

        return $this;
    }

    public function isFePreview(): bool
    {
        return $this->properties['fePreview'];
    }

    public function setFePreview(bool $fePreview): self
    {
        $this->properties['fePreview'] = $fePreview;

        return $this;
    }

    public function updateFromHtmlHeadBag(HtmlHeadBag $bag): self
    {
        return $this->setTitle($bag->getTitle());
    }
}
