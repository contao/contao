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
use Contao\CoreBundle\DependencyInjection\Attribute\AsPage;
use Contao\CoreBundle\Exception\NoActivePageFoundException;
use Contao\CoreBundle\Routing\Page\DynamicRouteInterface;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\PageModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * @internal
 */
#[AsPage(contentComposition: false)]
class RootPageController extends AbstractController implements DynamicRouteInterface
{
    public function __construct(private PageRegistry $pageRegistry, private LoggerInterface|null $logger = null)
    {
    }

    public function __invoke(PageModel $pageModel): Response
    {
        $nextPage = $this->getNextPage($pageModel->id);

        return $this->redirect($nextPage->getAbsoluteUrl());
    }

    private function getNextPage(int $rootPageId): PageModel
    {
        $nextPage = $this->getContaoAdapter(PageModel::class)->findFirstPublishedByPid($rootPageId);

        if ($nextPage instanceof PageModel) {
            return $nextPage;
        }

        $this->logger?->error(sprintf('No active page found under root page "%s"', $rootPageId));

        throw new NoActivePageFoundException('No active page found under root page.');
    }

    public function configurePageRoute(PageRoute $route): PageRoute
    {
        try {
            $pageModel = $route->getPageModel();
            $route = $this->pageRegistry->getRoute($this->getNextPage($pageModel->id));
            $route->setDefault('pageModel', $pageModel);

            return $route;
        } catch (NoActivePageFoundException $exception) {
            throw new ResourceNotFoundException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function getUrlSuffixes(): array
    {
        return [];
    }
}
