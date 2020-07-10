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
use Contao\CoreBundle\Routing\Page\CompositionAwareInterface;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Routing\Page\PageRouteEnhancerInterface;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

class RootPageController extends AbstractController implements PageRouteEnhancerInterface, CompositionAwareInterface
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function __invoke(PageModel $pageModel): Response
    {
        if ('root' !== $pageModel->type) {
            throw new \InvalidArgumentException('Invalid page type');
        }

        return $this->redirectToContent($this->getNextPage((int) $pageModel->id), [], 303);
    }

    public function enhancePageRoute(PageRoute $route): Route
    {
        return $route;
    }

    public function getUrlSuffixes(): array
    {
        return $this->connection
            ->query("SELECT DISTINCT urlSuffix FROM tl_page WHERE type='root'")
            ->fetchAll(FetchMode::COLUMN)
        ;
    }

    public function supportsContentComposition(PageModel $pageModel): bool
    {
        return false;
    }

    private function getNextPage(int $rootPageId): PageModel
    {
        $this->initializeContaoFramework();

        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->get('contao.framework')->getAdapter(PageModel::class);

        $nextPage = $pageAdapter->findFirstPublishedByPid($rootPageId);

        if (null !== $nextPage) {
            return $nextPage;
        }

        if (null !== ($logger = $this->get('logger'))) {
            $logger->error(
                'No active page found under root page "'.$rootPageId.'"',
                    ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]
            );
        }

        throw new NoActivePageFoundException('No active page found under root page.');
    }
}
