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

use Contao\CoreBundle\DependencyInjection\Attribute\AsPage;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[AsPage]
class RegularPageController extends AbstractPageController
{
    public function __invoke(PageModel $pageModel): Response
    {
        return $this->renderPage($pageModel);
    }
}
