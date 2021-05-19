<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext;

use Contao\CoreBundle\Event\JsonLdEvent;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\PageModel;

class ContaoWebpageResponseContext extends WebpageResponseContext
{
    /**
     * @var PageModel
     */
    private $pageModel;

    /**
     * @var bool
     */
    private $isSearchable;

    public function __construct(JsonLdManager $jsonLdManager, PageModel $pageModel)
    {
        parent::__construct($jsonLdManager);

        $this->pageModel = $pageModel;

        $this
            ->setTitle($pageModel->pageTitle ?: $pageModel->title ?: '')
            ->setMetaDescription(str_replace(["\n", "\r", '"'], [' ', '', ''], $pageModel->description ?: ''))
            ->setIsSearchable(!(bool) $pageModel->noSearch)
        ;

        if ($pageModel->robots) {
            $this->setMetaRobots($pageModel->robots);
        }
    }

    public function getPage(): PageModel
    {
        return $this->pageModel;
    }

    public function isSearchable(): bool
    {
        return $this->isSearchable;
    }

    public function setIsSearchable(bool $isSearchable): ContaoWebpageResponseContext
    {
        $this->isSearchable = $isSearchable;
        return $this;
    }
}
