<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Model\Collection;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Symfony\Cmf\Component\Routing\Candidates\CandidatesInterface;
use Symfony\Component\HttpFoundation\Request;

trait CandidatePagesTrait
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
     * @var CandidatesInterface
     */
    private $candidates;

    /**
     * @return array<PageModel>
     */
    private function findCandidatePages(Request $request): array
    {
        $candidates = $this->candidates->getCandidates($request);

        if (empty($candidates)) {
            return [];
        }

        $ids = [];
        $aliases = [];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                $ids[] = (int) $candidate;
            } else {
                $aliases[] = $this->connection->quote($candidate);
            }
        }

        $conditions = [];

        if (!empty($ids)) {
            $conditions[] = 'tl_page.id IN ('.implode(',', $ids).')';
        }

        if (!empty($aliases)) {
            $conditions[] = 'tl_page.alias IN ('.implode(',', $aliases).')';
        }

        /** @var PageModel $pageModel */
        $pageModel = $this->framework->getAdapter(PageModel::class);
        $pages = $pageModel->findBy([implode(' OR ', $conditions)], []);

        if (!$pages instanceof Collection) {
            return [];
        }

        /** @var array<PageModel> */
        return $pages->getModels();
    }
}
