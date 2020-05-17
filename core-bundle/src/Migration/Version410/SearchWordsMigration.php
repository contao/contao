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
            INSERT INTO tl_search_words (word, documentFrequency)
            SELECT tl_search_index.word, COUNT(*) as documentFrequency
            FROM tl_search_index
            GROUP BY tl_search_index.word
        ');

        $this->connection->query('
            ALTER TABLE tl_search_index 
            ADD wordId INT UNSIGNED NULL
        ');

        $this->connection->query('
            UPDATE tl_search_index
            JOIN tl_search_words 
                ON tl_search_words.word = tl_search_index.word
            SET tl_search_index.wordId = tl_search_words.id
        ');

        $this->connection->query('
            ALTER TABLE tl_search_index 
            CHANGE wordId wordId INT UNSIGNED NOT NULL
        ');

        $this->connection->query('
            ALTER TABLE
                tl_search
            ADD
                vectorLength DOUBLE NULL
        ');

        $this->connection->query('
            UPDATE tl_search
            JOIN (
                SELECT 
                    tl_search_index.pid,
                    SQRT(
                        SUM(
                            POW(
                                (
                                    (1 + LOG(relevance))
                                    * LOG((
                                        SELECT COUNT(*) FROM tl_search
                                    ) / documentFrequency)
                                ),
                                2
                            )
                        )
                    ) as vectorLength
                FROM tl_search_index
                JOIN tl_search_words 
                    ON tl_search_index.wordId = tl_search_words.id
                GROUP BY tl_search_index.pid
            ) si 
                ON si.pid = tl_search.id
            SET tl_search.vectorLength = si.vectorLength
        ');

        $this->connection->query('
            ALTER TABLE
                tl_search
            CHANGE
                vectorLength vectorLength DOUBLE NOT NULL
        ');

        return new MigrationResult(true, '');
    }
}
