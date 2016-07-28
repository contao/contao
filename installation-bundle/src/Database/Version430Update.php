<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Database;

/**
 * Runs the version 4.3.0 update.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class Version430Update extends AbstractVersionUpdate
{
    /**
     * {@inheritdoc}
     */
    public function shouldBeRun()
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist('tl_layout')) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_layout');

        return !isset($columns['combineScripts']);
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->connection->query("ALTER TABLE `tl_layout` ADD `combineScripts` char(1) NOT NULL default ''");
        $this->connection->query("UPDATE tl_layout SET combineScripts='1'");
    }
}
