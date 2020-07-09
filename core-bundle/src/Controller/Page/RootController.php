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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Routing\Page\CompositionAwareInterface;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Routing\Page\PageRouteEnhancerInterface;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;

class RootController extends AbstractController implements PageRouteEnhancerInterface, CompositionAwareInterface
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    public function __construct(ContaoFramework $framework, Connection $connection, LoggerInterface $logger = null)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function __invoke(PageModel $pageModel): Response
    {
        if ('root' !== $pageModel->type) {
            throw new \InvalidArgumentException('Invalid page type');
        }

        return $this->redirectToContent($this->getNextPage($pageModel->id));
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

    public function supportsContentComposition(PageModel $pageModel = null): bool
    {
        return false;
    }

    private function getNextPage($rootPageId): PageModel
    {
        $this->framework->initialize();

        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->framework->getAdapter(PageModel::class);

        $nextPage = $pageAdapter->findFirstPublishedByPid($rootPageId);

        // No published pages yet
        if (null === $nextPage) {
            if (null !== $this->logger) {
                $this->logger->error(
                    'No active page found under root page "'.$rootPageId.'"',
                    ['contao' => new ContaoContext(__METHOD__)]
                );
            }

            throw new NoActivePageFoundException('No active page found under root page.');
        }

        return $nextPage;
    }
}
