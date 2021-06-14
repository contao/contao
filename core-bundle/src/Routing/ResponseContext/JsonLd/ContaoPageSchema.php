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
     * @var string
     */
    private $title;

    /**
     * @var int
     */
    private $pageId;

    /**
     * @var bool
     */
    private $noSearch;

    /**
     * @var bool
     */
    private $protected;

    /**
     * @var array<int>
     */
    private $groups;

    /**
     * @var bool
     */
    private $fePreview;

    /**
     * ContaoPageSchema constructor.
     *
     * @param array<int> $groups
     */
    public function __construct(string $title, int $pageId, bool $noSearch, bool $protected, array $groups, bool $fePreview)
    {
        $this->title = $title;
        $this->pageId = $pageId;
        $this->noSearch = $noSearch;
        $this->protected = $protected;
        $this->groups = $groups;
        $this->fePreview = $fePreview;
    }

    public function getContext(): string
    {
        return 'https://schema.contao.org/';
    }

    public function getType(): string
    {
        return 'contao:Page';
    }

    public function toArray(): array
    {
        $this->setProperty('contao:title', $this->title);
        $this->setProperty('contao:pageId', $this->pageId);
        $this->setProperty('contao:noSearch', $this->noSearch);
        $this->setProperty('contao:protected', $this->protected);
        $this->setProperty('contao:groups', $this->groups);
        $this->setProperty('contao:fePreview', $this->fePreview);

        $data = parent::toArray();

        $data['@context'] = [
            'contao' => $data['@context'],
        ];

        return $data;
    }

    public function updateFromHtmlHeadBag(HtmlHeadBag $bag): self
    {
        $this->title = $bag->getTitle();

        return $this;
    }
}
