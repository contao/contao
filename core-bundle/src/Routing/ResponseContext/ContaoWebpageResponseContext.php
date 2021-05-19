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

class ContaoWebpageResponseContext extends WebpageResponseContext
{
    public function __construct(PageModel $pageModel)
    {
        $this
            ->setTitle($pageModel->pageTitle ?: $pageModel->title ?: '')
            ->setMetaDescription(str_replace(["\n", "\r", '"'], [' ', '', ''], $pageModel->description ?: ''))
        ;

        if ($pageModel->robots) {
            $this->setMetaRobots($pageModel->robots);
        }
    }
}
