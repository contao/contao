<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\Candidates;

use Contao\CoreBundle\Routing\Page\PageRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Symfony\Component\HttpFoundation\Request;

class PageCandidates extends AbstractCandidates
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var PageRegistry
     */
    private $pageRegistry;

    /**
     * @var bool
     */
    private $initialized = false;

    public function __construct(Connection $connection, PageRegistry $pageRegistry)
    {
        parent::__construct([], []);

        $this->connection = $connection;
        $this->pageRegistry = $pageRegistry;
    }

    public function getCandidates(Request $request): array
    {
        $this->initialize();

        return parent::getCandidates($request);
    }

    /**
     * Lazy-initialize because we don't want to query the database when creating the service.
     */
    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;

        $urlPrefixes = $this->connection
            ->query("SELECT DISTINCT urlPrefix FROM tl_page WHERE type='root'")
            ->fetchAll(FetchMode::COLUMN)
        ;

        $this->setUrlPrefixes($urlPrefixes);
        $this->setUrlSuffixes($this->pageRegistry->getUrlSuffixes());
    }
}
