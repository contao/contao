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

use Contao\InsertTags;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectPageController extends AbstractPageController
{
    protected function getResponse(PageModel $pageModel, Request $request): Response
    {
        /** @var InsertTags $insertTags */
        $insertTags = $this->get('contao.framework')->createInstance(InsertTags::class);

        return $this->redirect(
            $insertTags->replace($pageModel->url, false),
            'temporary' === $pageModel->redirect ? Response::HTTP_SEE_OTHER : Response::HTTP_MOVED_PERMANENTLY
        );
    }
}
