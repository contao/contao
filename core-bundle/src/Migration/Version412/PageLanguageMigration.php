<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version412;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\CoreBundle\Util\LocaleUtil;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class PageLanguageMigration extends AbstractMigration
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_page'])) {
            return false;
        }

        $pageColumns = $schemaManager->listTableColumns('tl_page');

        if (!isset($pageColumns['language'])) {
            return false;
        }

        $count = $this->connection->fetchOne("
            SELECT
                COUNT(*)
            FROM
                tl_page
            WHERE
                type='root' AND SUBSTRING(language, 3, 1) = '-'
        ");

        return $count > 0;
    }

    public function run(): MigrationResult
    {
        $pages = $this->connection->fetchAllAssociative("
            SELECT
                id, language
            FROM
                tl_page
            WHERE
                type='root' AND SUBSTRING(language, 3, 1) = '-'
        ");

        foreach ($pages as $page) {
            $this->connection->update(
                'tl_page',
                ['language' => LocaleUtil::canonicalize($page['language'])],
                ['id' => $page['id']]
            );
        }

        return $this->createResult(true);
    }
}
