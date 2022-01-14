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

use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\Page\DynamicRouteInterface;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\ServiceAnnotation\Page;
use Contao\PageModel;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Page(contentComposition=false)
 *
 * @internal
 */
class RedirectPageController implements DynamicRouteInterface
{
    private InsertTagParser $insertTags;

    public function __construct(InsertTagParser $insertTags)
    {
        $this->insertTags = $insertTags;
    }

    public function __invoke(PageModel $pageModel): Response
    {
        return new RedirectResponse(
            $this->insertTags->render($pageModel->url),
            'temporary' === $pageModel->redirect ? Response::HTTP_FOUND : Response::HTTP_MOVED_PERMANENTLY
        );
    }

    public function configurePageRoute(PageRoute $route): void
    {
        $pageModel = $route->getPageModel();

        $route->setDefault('_controller', RedirectController::class);
        $route->setDefault('path', $this->insertTags->render($pageModel->url));
        $route->setDefault('permanent', 'temporary' !== $pageModel->redirect);
    }

    public function getUrlSuffixes(): array
    {
        return [];
    }
}
