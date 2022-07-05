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

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class PlayerColorMigration extends AbstractMigration
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_content'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_content');

        if (!isset($columns['playercolor']) || $columns['playercolor']->getLength() <= 6) {
            return false;
        }

        return (bool) $this->connection
            ->executeQuery("
                SELECT EXISTS(
                    SELECT playerColor
                    FROM tl_content
                    WHERE
                        CHAR_LENGTH(playerColor) > 6
                        AND playerColor LIKE 'com_%'
                )
            ")
            ->fetchOne()
        ;
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement("
            UPDATE tl_content
            SET playerColor = ''
            WHERE
                CHAR_LENGTH(playerColor) > 6
                AND playerColor LIKE 'com_%'
        ");

        return $this->createResult(true);
    }
}
