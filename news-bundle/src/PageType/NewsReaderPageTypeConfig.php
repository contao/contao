<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\PageType;

use Contao\CoreBundle\PageType\PageTypeConfig;
use Contao\CoreBundle\PageType\PageTypeInterface;
use Contao\NewsModel;
use Contao\PageModel;

class NewsReaderPageTypeConfig extends PageTypeConfig
{
    /**
     * @var NewsModel
     */
    private $newsModel;

    public function __construct(
        PageTypeInterface $pageType,
        PageModel $pageModel,
        NewsModel $newsModel,
        array $options = []
    ) {
        parent::__construct($pageType, $pageModel, $options);

        $this->newsModel        = $newsModel;
    }

    public function getNewsModel() : NewsModel
    {
        return $this->newsModel;
    }
}
