<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version403;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class Version430Update extends AbstractMigration
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getName(): string
    {
        return 'Contao 4.3.0 Update';
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_layout'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_layout');

        return !isset($columns['combinescripts']);
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement('
            ALTER TABLE
                tl_layout
            CHANGE
                sections sections blob NULL
        ');

        $layouts = $this->connection->fetchAllAssociative("
            SELECT
                id, sections, sPosition
            FROM
                tl_layout
            WHERE
                sections != ''
        ");

        foreach ($layouts as $layout) {
            $sections = StringUtil::trimsplit(',', $layout['sections']);

            if (!empty($sections) && \is_array($sections)) {
                $set = [];

                foreach ($sections as $section) {
                    $set[$section] = [
                        'title' => $section,
                        'id' => $section,
                        'template' => 'block_section',
                        'position' => $layout['sPosition'],
                    ];
                }

                $this->connection->executeStatement(
                    'UPDATE tl_layout SET sections = :sections WHERE id = :id',
                    ['sections' => serialize(array_values($set)), 'id' => $layout['id']]
                );
            }
        }

        $this->connection->executeStatement("
            ALTER TABLE
                tl_layout
            ADD
                combineScripts char(1) NOT NULL default ''
        ");

        $this->connection->executeStatement("
            UPDATE
                tl_layout
            SET
                combineScripts = '1'
        ");

        return $this->createResult(true);
    }
}
