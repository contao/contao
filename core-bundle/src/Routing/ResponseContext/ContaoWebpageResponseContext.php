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

use Contao\PageModel;
use Contao\StringUtil;

class ContaoWebpageResponseContext extends WebpageResponseContext
{
    public function __construct(PageModel $pageModel)
    {
        $title = $pageModel->pageTitle ?: StringUtil::inputEncodedToPlainText($pageModel->title ?: '');

        $this
            ->setTitle($title ?: '')
            ->setMetaDescription(StringUtil::inputEncodedToPlainText($pageModel->description ?: ''))
        ;

        if ($pageModel->robots) {
            $this->setMetaRobots($pageModel->robots);
        }
    }
}
