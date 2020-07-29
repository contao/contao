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
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

class RootPageController extends AbstractController implements DynamicRouteInterface
{
    public function __invoke(PageModel $pageModel): Response
    {
        try {
            $nextPage = $this->getNextPage($pageModel);
        } catch (NoActivePageFoundException $exception) {
            if (null !== ($logger = $this->get('logger'))) {
                $logger->error(
                    $exception->getMessage(),
                    ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]
                );
            }

            throw $exception;
        }

        return $this->redirectToContent($nextPage);
    }

    public function enhancePageRoute(PageRoute $route): Route
    {
        return $route->setTargetUrl($this->generateContentUrl($this->getNextPage($route->getPageModel())));
    }

    public function getUrlSuffixes(): array
    {
        return [];
    }

    private function getNextPage(PageModel $rootPageModel): PageModel
    {
        $this->initializeContaoFramework();

        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->get('contao.framework')->getAdapter(PageModel::class);
        $nextPageModel = $pageAdapter->findFirstPublishedByPid($rootPageModel->id);

        if (null !== $nextPageModel) {
            return $nextPageModel;
        }

        throw new NoActivePageFoundException('No active page found under root page ID '.$rootPageModel->id);
    }
}
