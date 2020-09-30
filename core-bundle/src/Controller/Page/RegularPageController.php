<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\Page;

use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RegularPageController extends AbstractPageController
{
    protected function getResponse(PageModel $pageModel, Request $request): Response
    {
        return $this->renderPageContent($pageModel);
    }
}
