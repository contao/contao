<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Database;

/**
 * Runs the version 4.4.0 update.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class Version440Update extends AbstractVersionUpdate
{
    /**
     * {@inheritdoc}
     */
    public function shouldBeRun()
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_content'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_content');

        return !isset($columns['imagetitle']);
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->connection->query("ALTER TABLE `tl_content` CHANGE `title` `imageTitle` varchar(255) NOT NULL DEFAULT ''");
        $this->connection->query("UPDATE tl_content SET overwriteMeta='1' WHERE alt!='' OR imageTitle!='' OR imageUrl!='' OR caption!=''");
    }
}
