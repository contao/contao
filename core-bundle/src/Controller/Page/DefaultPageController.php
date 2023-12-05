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

use Contao\CoreBundle\Twig\LayoutTemplate;
use Contao\LayoutModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultPageController extends AbstractPageController
{
    protected function getResponse(LayoutTemplate $template, LayoutModel $model, Request $request): Response
    {
        return $template->getResponse();
    }
}
