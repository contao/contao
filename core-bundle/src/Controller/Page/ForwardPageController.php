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
use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Routing\Page\DynamicRouteInterface;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\PageModel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;

class ForwardPageController extends AbstractController implements DynamicRouteInterface
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
        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->get('contao.framework')->getAdapter(PageModel::class);

        if ($pageModel->jumpTo) {
            $forwardPage = $pageAdapter->findPublishedById($pageModel->jumpTo);
        } else {
            $forwardPage = $pageAdapter->findFirstPublishedRegularByPid($pageModel->id);
        }

        // Forward page does not exist
        if (!$forwardPage instanceof PageModel) {
            throw new ForwardPageNotFoundException('Forward page not found');
        }

        return $this->generateContentUrl($forwardPage, [], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
