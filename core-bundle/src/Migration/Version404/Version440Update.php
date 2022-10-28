<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version404;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class Version440Update extends AbstractMigration
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getName(): string
    {
        return 'Contao 4.4.0 Update';
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_content'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_content');

        return !isset($columns['imagetitle']);
    }

    public function run(): MigrationResult
    {
        // Add the js_autofocus.html5 template
        $layouts = $this->connection->fetchAllAssociative('
            SELECT
                id, scripts
            FROM
                tl_layout
        ');

        foreach ($layouts as $layout) {
            $scripts = StringUtil::deserialize($layout['scripts']);

            if (!empty($scripts) && \is_array($scripts)) {
                $scripts[] = 'js_autofocus';

                $this->connection->executeStatement(
                    'UPDATE tl_layout SET scripts = :scripts WHERE id = :id',
                    ['scripts' => serialize(array_values(array_unique($scripts))), 'id' => $layout['id']]
                );
            }
        }

        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['tl_calendar_events'])) {
            $this->enableOverwriteMeta('tl_calendar_events');
        }

        if ($schemaManager->tablesExist(['tl_faq'])) {
            $this->enableOverwriteMeta('tl_faq');
        }

        if ($schemaManager->tablesExist(['tl_news'])) {
            $this->enableOverwriteMeta('tl_news');
        }

        $this->connection->executeStatement("
            ALTER TABLE
                tl_content
            CHANGE
                title imageTitle varchar(255) NOT NULL DEFAULT ''
        ");

        $this->connection->executeStatement("
            ALTER TABLE
                tl_content
            ADD
                overwriteMeta CHAR(1) DEFAULT '' NOT NULL
        ");

        $this->connection->executeStatement("
            UPDATE
                tl_content
            SET
                overwriteMeta = '1'
            WHERE
                alt != '' OR imageTitle != '' OR imageUrl != '' OR caption != ''
        ");

        return $this->createResult(true);
    }

    private function enableOverwriteMeta(string $table): void
    {
        $this->connection->executeStatement("
            ALTER TABLE
                $table
            ADD
                overwriteMeta CHAR(1) DEFAULT '' NOT NULL
        ");

        $this->connection->executeStatement("
            UPDATE
                $table
            SET
                overwriteMeta = '1'
            WHERE
                alt != '' OR imageUrl != '' OR caption != ''
        ");
    }
}
