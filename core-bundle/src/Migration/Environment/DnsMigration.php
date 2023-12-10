<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Environment;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class DnsMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $db,
        private readonly array $mapping,
    ) {
    }

    public function shouldRun(): bool
    {
        if (!$this->mapping) {
            return false;
        }

        $schemaManager = $this->db->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_page'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_page');

        if (!isset($columns['dns']) || !isset($columns['type']) || !isset($columns['usessl'])) {
            return false;
        }

        foreach ($this->mapping as $from => $to) {
            $from = $this->parseHost($from);
            $to = $this->parseHost($to);

            $qb = $this->db->createQueryBuilder()
                ->select('TRUE')
                ->from('tl_page')
                ->where("type = 'root'")
            ;

            if ($from['scheme']) {
                $qb->andWhere('https:' === $from['scheme'] ? 'useSSL = 1' : 'useSSL = 0');
            }

            if (null !== $from['host']) {
                $qb
                    ->andWhere('dns = :fromHost')
                    ->setParameter('fromHost', $from['host'])
                ;
            }

            $or = [];

            if ($to['scheme']) {
                $or[] = 'https:' === $to['scheme'] ? 'useSSL = 0' : 'useSSL = 1';
            }

            if (null !== $to['host']) {
                $or[] = 'dns != :toHost';
                $qb->setParameter('toHost', $to['host']);
            }

            if ($or) {
                $qb->andWhere($qb->expr()->or(...$or));
            }

            if ($qb->executeQuery()->fetchOne()) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        foreach ($this->mapping as $from => $to) {
            $from = $this->parseHost($from);
            $to = $this->parseHost($to);

            $qb = $this->db->createQueryBuilder()
                ->update('tl_page')
                ->where("type = 'root'")
            ;

            if ($from['scheme']) {
                $qb->andWhere('https:' === $from['scheme'] ? 'useSSL = 1' : 'useSSL != 1');
            }

            if (null !== $from['host']) {
                $qb
                    ->andWhere('dns = :fromHost')
                    ->setParameter('fromHost', $from['host'])
                ;
            }

            if ($to['scheme']) {
                $qb
                    ->set('useSSL', ':useSSL')
                    ->setParameter('useSSL', 'https:' === $to['scheme'], ParameterType::BOOLEAN)
                ;
            }

            if (null !== $to['host']) {
                $qb
                    ->set('dns', ':toHost')
                    ->setParameter('toHost', $to['host'])
                ;
            }

            $qb->executeQuery();
        }

        return $this->createResult(true);
    }

    private function parseHost(string $host): array
    {
        [$host, $scheme] = array_reverse(explode('//', $host)) + [null, null];

        return [
            'host' => $host,
            'scheme' => $scheme,
        ];
    }
}
