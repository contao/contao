<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\Content;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

class RootPageProvider extends AbstractPageProvider
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(ContaoFramework $framework, Connection $connection)
    {
        $this->framework = $framework;
        $this->connection = $connection;
    }

    public function getRouteForPage(PageModel $pageModel, $content = null, Request $request = null): Route
    {
        if ('root' !== $pageModel->type) {
            throw new RouteNotFoundException('Invalid page type');
        }

        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->framework->getAdapter(PageModel::class);

        $nextPage = $pageAdapter->findFirstPublishedByPid($pageModel->id);

        if (!$nextPage instanceof PageModel) {
            throw new RouteNotFoundException('No active page found under root page.');
        }

        $route = new PageRoute($pageModel);
        $route->addDefaults([
            '_controller' => RedirectController::class.'::urlRedirectAction',
            'path' => $nextPage->getAbsoluteUrl(),
            'permanent' => false,
        ]);

        return $route;
    }

    public function getUrlSuffixes(): array
    {
        return $this->connection
            ->query("SELECT DISTINCT urlSuffix FROM tl_page WHERE type='root'")
            ->fetchAll(FetchMode::COLUMN)
        ;
    }

    public function supportContentComposition(PageModel $pageModel = null): bool
    {
        return false;
    }
}
