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
use Contao\CoreBundle\Exception\NoActivePageFoundException;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Routing\Page\DynamicRouteInterface;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\ServiceAnnotation\Page;
use Contao\PageModel;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Page(contentComposition=false)
 *
 * @internal
 */
class RootPageController extends AbstractController implements DynamicRouteInterface
{
    public function __invoke(PageModel $pageModel): Response
    {
        $nextPage = $this->getNextPage((int) $pageModel->id);

        return $this->redirect($nextPage->getAbsoluteUrl());
    }

    public function configurePageRoute(PageRoute $route): void
    {
        $nextPage = $this->getContaoAdapter(PageModel::class)->findFirstPublishedByPid((int) $route->getPageModel()->id);

        if (null === $nextPage) {
            return;
        }

        $route->setDefault('_controller', RedirectController::class);
        $route->setDefault('path', $nextPage->getAbsoluteUrl());
        $route->setDefault('permanent', true);
    }

    public function getUrlSuffixes(): array
    {
        return [];
    }

    private function getNextPage(int $rootPageId): PageModel
    {
        $nextPage = $this->getContaoAdapter(PageModel::class)->findFirstPublishedByPid($rootPageId);

        if ($nextPage instanceof PageModel) {
            return $nextPage;
        }

        if ($this->container->has('logger')) {
            $this->container->get('logger')->error(
                'No active page found under root page "'.$rootPageId.'"',
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]
            );
        }

        throw new NoActivePageFoundException('No active page found under root page.');
    }
}
