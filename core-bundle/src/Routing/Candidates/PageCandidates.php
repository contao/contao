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
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Query\QueryBuilder;
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

        $candidates = parent::getCandidates($request);

        $qb = $this->connection->createQueryBuilder();
        $qb->select('id')->from('tl_page');

        $hasRoot = $this->addRootQuery($candidates, $qb, $request->getHttpHost());
        $hasRegex = $this->addRegexQuery($qb, $request->getPathInfo());

        if ($hasRoot || $hasRegex) {
            /** @var Result $result */
            $result = $qb->execute();

            return array_unique(array_merge($candidates, $result->fetchFirstColumn()));
        }

        return $candidates;
    }

    private function addRootQuery(array &$candidates, QueryBuilder $queryBuilder, string $httpHost): bool
    {
        if (!\in_array('index', $candidates, true)) {
            return false;
        }

        $candidates[] = '/';

        $queryBuilder->orWhere("type='root' AND (dns=:httpHost OR dns='')");
        $queryBuilder->setParameter('httpHost', $httpHost);

        return true;
    }

    private function addRegexQuery(QueryBuilder $queryBuilder, string $pathInfo): bool
    {
        $pathMap = $this->pageRegistry->getPathRegex();

        if (empty($pathMap)) {
            return false;
        }

        $paths = [];

        foreach ($pathMap as $type => $pathRegex) {
            $paths[] = '(?P<'.$type.'>'.substr($pathRegex, 2, strrpos($pathRegex, '$') - 2).')';
        }

        $prefixes = array_map(
            static function ($prefix) {
                return $prefix ? preg_quote('/'.$prefix, '#') : '';
            },
            $this->urlPrefixes
        );

        preg_match_all(
            '#^('.implode('|', $prefixes).')('.implode('|', $paths).')('.implode('|', array_map('preg_quote', $this->urlSuffixes)).')'.'$#sD',
            $pathInfo,
            $matches
        );

        $types = array_keys(array_intersect_key($pathMap, array_filter($matches)));

        if (empty($types)) {
            return false;
        }

        $queryBuilder
            ->orWhere('type IN (:types)')
            ->setParameter('types', $types, Connection::PARAM_STR_ARRAY)
        ;

        return true;
    }

    /**
     * Lazy-initialize because we do not want to query the database when creating the service.
     */
    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;
        $this->urlPrefixes = $this->pageRegistry->getUrlPrefixes();
        $this->urlSuffixes = $this->pageRegistry->getUrlSuffixes();
    }
}
