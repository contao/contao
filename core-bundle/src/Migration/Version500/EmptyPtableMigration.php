<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version500;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class EmptyPtableMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_content'])) {
            return false;
        }

        $test = $this->connection->fetchOne("SELECT TRUE FROM tl_content WHERE ptable='' LIMIT 1");

        return false !== $test;
    }

    public function run(): MigrationResult
    {
        $this->connection->update('tl_content', ['ptable' => 'tl_article'], ['ptable' => '']);

        return $this->createResult(true);
    }
}
