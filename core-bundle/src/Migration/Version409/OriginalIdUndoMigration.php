<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version409;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class OriginalIdUndoMigration extends AbstractMigration
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(Connection $connection, ContaoFramework $framework)
    {
        $this->connection = $connection;
        $this->framework = $framework;
    }

    public function shouldRun(): bool
    {
        $missingOriginalIds = $this->connection->executeQuery('
            SELECT
                COUNT(id)
            FROM
                tl_undo
            WHERE
                originalId = 0
        ')->fetchOne();

        return (int) $missingOriginalIds > 0;
    }

    public function run(): MigrationResult
    {
        $this->framework->initialize();

        $rows = $this->connection->executeQuery('
            SELECT
                id, fromTable, data
            FROM
                tl_undo
            WHERE
                originalId = 0
        ')->fetchAllAssociative();

        foreach ($rows as $row) {
            $data = StringUtil::deserialize($row['data'], true);
            $originalData = $data[$row['fromTable']][0];
            $originalId = $originalData['id'];

            $this->connection->update('tl_undo', [
                'originalId' => $originalId,
            ], [
                'id' => $row['id'],
            ]);
        }

        return $this->createResult(true);
    }
}
