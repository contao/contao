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

class Version470Update extends AbstractVersionUpdate
{
    /**
     * {@inheritdoc}
     */
    public function shouldBeRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_layout'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_layout');

        return !isset($columns['minifymarkup']);
    }

    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        $this->connection->query("
            ALTER TABLE
                tl_layout
            ADD
                minifyMarkup char(1) NOT NULL default ''
        ");

        // Enable the "minifyMarkup" option if it was enabled before
        if (isset($GLOBALS['TL_CONFIG']['minifyMarkup']) && $GLOBALS['TL_CONFIG']['minifyMarkup']) {
            $this->connection->query("
                UPDATE
                    tl_layout
                SET
                    minifyMarkup = '1'
            ");
        }
    }
}
