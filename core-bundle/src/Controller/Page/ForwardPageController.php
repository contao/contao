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
use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Routing\Page\DynamicRouteInterface;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\PageModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsPage(contentComposition: false)]
class ForwardPageController extends AbstractController implements DynamicRouteInterface
{
    public function __construct(
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    public function __invoke(Request $request, PageModel $pageModel): RedirectResponse
    {
        $forwardPage = $this->getForwardPage($pageModel);

        $queryString = '';

        if ([] !== ($query = $request->query->all())) {
            $queryString = '?'.http_build_query($query);
        }

        return $this->redirect(
            $this->generateContentUrl($forwardPage, $request->attributes->all(), UrlGeneratorInterface::ABSOLUTE_URL).$queryString,
            'temporary' === $pageModel->redirect ? Response::HTTP_SEE_OTHER : Response::HTTP_MOVED_PERMANENTLY,
        );
    }

    public function configurePageRoute(PageRoute $route): void
    {
        $pageModel = $route->getPageModel();

        if ($pageModel->alwaysForward) {
            return;
        }

        $route->setPath('/'.($pageModel->alias ?: $pageModel->id));

        $requirements = $route->getRequirements();
        unset($requirements['parameters']);

        $defaults = $route->getDefaults();
        unset($defaults['parameters']);

        $route->setRequirements($requirements);
        $route->setDefaults($defaults);
    }

    public function getUrlSuffixes(): array
    {
        return [];
    }

    private function getForwardPage(PageModel $pageModel): PageModel
    {
        $pageAdapter = $this->getContaoAdapter(PageModel::class);

        if ($pageModel->jumpTo) {
            $forwardPage = $pageAdapter->findPublishedById($pageModel->jumpTo);
        } else {
            $forwardPage = $pageAdapter->findFirstPublishedRegularByPid($pageModel->id);
        }

        if ($forwardPage instanceof PageModel) {
            return $forwardPage;
        }

        $this->logger?->error(\sprintf('Forward page ID "%s" does not exist', $pageModel->jumpTo));

        throw new ForwardPageNotFoundException('Forward page not found');
    }
}
