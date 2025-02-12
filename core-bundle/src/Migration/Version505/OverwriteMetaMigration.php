<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version505;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\StringType;

/**
 * @internal
 */
class OverwriteMetaMigration extends AbstractMigration
{
    protected const TABLE_NAME = 'tl_content';

    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(static::TABLE_NAME)) {
            return false;
        }

        $columns = $schemaManager->listTableColumns(static::TABLE_NAME);

        if (
            !isset($columns['overwritemeta'], $columns['alt'], $columns['imageurl'], $columns['caption'], $columns['imagetitle'])
            || !$columns['alt']->getType() instanceof StringType
            || !$columns['caption']->getType() instanceof StringType
            || !$columns['imagetitle']->getType() instanceof StringType
        ) {
            return false;
        }

        $test = $this->connection->fetchOne(
            \sprintf(<<<'SQL'
                SELECT TRUE
                FROM %s
                WHERE overwriteMeta = 1 AND (
                    alt = ''
                    OR imageUrl = ''
                    OR caption = ''
                    OR imageTitle = ''
                )
                LIMIT 1
                SQL,
                static::TABLE_NAME,
            ),
        );

        return false !== $test;
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement(
            \sprintf(<<<'SQL'
                UPDATE %s
                SET
                    alt = IF(alt != '', alt, '{{empty}}'),
                    imageUrl = IF(imageUrl != '', imageUrl, '{{empty}}'),
                    caption = IF(caption != '', caption, '{{empty}}'),
                    imageTitle = IF(imageTitle != '', imageTitle, '{{empty}}')
                WHERE overwriteMeta = 1
                SQL,
                static::TABLE_NAME,
            ),
        );

        $this->connection->executeStatement(
            \sprintf(<<<'SQL'
                ALTER TABLE %s
                CHANGE alt alt TEXT DEFAULT NULL,
                CHANGE imageUrl imageUrl TEXT DEFAULT NULL,
                CHANGE caption caption TEXT DEFAULT NULL,
                CHANGE imageTitle imageTitle TEXT DEFAULT NULL
                SQL,
                static::TABLE_NAME,
            ),
        );

        return $this->createResult(true);
    }
}
