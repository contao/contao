<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version410;

use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

/**
 * @internal
 */
class SearchWordsMigration extends AbstractMigration
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var DcaSchemaProvider
     */
    private $schemaProvider;

    public function __construct(Connection $connection, DcaSchemaProvider $schemaProvider)
    {
        $this->connection = $connection;
        $this->schemaProvider = $schemaProvider;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (
            !$schemaManager->tablesExist('tl_search_index')
            || $schemaManager->tablesExist('tl_search_words')
        ) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_search_index');

        if (isset($columns['wordid'])) {
            return false;
        }

        return true;
    }

    public function run(): MigrationResult
    {
        $emptySchema = new Schema();

        $searchWordsSchema = new Schema([
            $this->schemaProvider->createSchema()->getTable('tl_search_words'),
        ]);

        $sql = $emptySchema->getMigrateToSql(
            $searchWordsSchema,
            $this->connection->getDatabasePlatform()
        );

        foreach ($sql as $query) {
            $this->connection->query($query);
        }

        $this->connection->query('
            ALTER TABLE tl_search_index 
            ADD wordId INT UNSIGNED DEFAULT 0 NOT NULL
        ');

        $this->connection->query('
            INSERT INTO tl_search_words (word)
            SELECT DISTINCT word
            FROM tl_search_index
        ');

        $this->connection->query('
            UPDATE tl_search_index 
            SET wordId = (
                SELECT id 
                FROM tl_search_words
                WHERE tl_search_words.word = tl_search_index.word
            )
        ');

        return new MigrationResult(true, '');
    }
}
