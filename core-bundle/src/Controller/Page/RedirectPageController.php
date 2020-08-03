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

use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Routing\Page\DynamicRouteInterface;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\InsertTags;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\Routing\Route;

class RedirectPageController extends AbstractController implements DynamicRouteInterface
{
    public function enhancePageRoute(PageRoute $route): Route
    {
        return $route->setTargetUrl($this->getTargetUrl($route->getPageModel()));
    }

    public function getUrlSuffixes(): array
    {
        return [];
    }

    private function getTargetUrl(PageModel $pageModel): string
    {
        if (0 === strncasecmp($pageModel->url, 'mailto:', 7)) {
            return StringUtil::encodeEmail($pageModel->url);
        }

        /** @var InsertTags $insertTags */
        $insertTags = $this->get('contao.framework')->createInstance(InsertTags::class);

        return $insertTags->replace($pageModel->url, false);
    }
}
