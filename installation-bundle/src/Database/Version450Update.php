<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Database;

class Version450Update extends AbstractVersionUpdate
{
    /**
     * {@inheritdoc}
     */
    public function shouldBeRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_module'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_module');

        if (!isset($columns['news_order'])) {
            return false;
        }

        return 32 !== $columns['news_order']->getLength();
    }

    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        $this->connection->query("
            UPDATE
                tl_module
            SET
                news_order = 'order_date_asc'
            WHERE
                news_order = 'ascending'
        ");

        $this->connection->query("
            UPDATE
                tl_module
            SET
                news_order = 'order_date_desc'
            WHERE
                news_order = 'descending'
        ");

        $this->connection->query("
            ALTER TABLE
                tl_module
            CHANGE
                news_order news_order VARCHAR(32) DEFAULT '' NOT NULL
        ");
    }
}
