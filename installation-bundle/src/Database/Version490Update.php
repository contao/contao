<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\Database;

/**
 * @internal
 */
class Version490Update extends AbstractVersionUpdate
{
    /**
     * {@inheritdoc}
     */
    public function shouldBeRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_cron'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_cron');

        return isset($columns['value']);
    }

    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        // Drop old tl_cron table
        if ($this->connection->getSchemaManager()->tablesExist(['tl_cron'])) {
            $this->connection->query('DROP TABLE tl_cron');
        }
    }
}
