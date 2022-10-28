<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version411;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class RemoveJsHighlightMigration extends AbstractMigration
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_layout'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_layout');

        if (!isset($columns['scripts'])) {
            return false;
        }

        $count = $this->connection->fetchOne("
            SELECT
                COUNT(*)
            FROM
                tl_layout
            WHERE
                scripts LIKE '%js_highlight%'
        ");

        return $count > 0;
    }

    public function run(): MigrationResult
    {
        $layouts = $this->connection->fetchAllAssociative("
            SELECT
                id, scripts
            FROM
                tl_layout
            WHERE
                scripts LIKE '%js_highlight%'
        ");

        foreach ($layouts as $layout) {
            $scripts = StringUtil::deserialize($layout['scripts']);

            if (!empty($scripts) && \is_array($scripts)) {
                $key = array_search('js_highlight', $scripts, true);

                if (false !== $key) {
                    unset($scripts[$key]);

                    $this->connection->executeStatement(
                        'UPDATE tl_layout SET scripts = :scripts WHERE id = :id',
                        ['scripts' => serialize(array_values($scripts)), 'id' => $layout['id']]
                    );
                }
            }
        }

        return $this->createResult(true);
    }
}
