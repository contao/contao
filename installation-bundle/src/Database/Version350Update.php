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

class Version350Update extends AbstractVersionUpdate
{
    /**
     * {@inheritdoc}
     */
    public function shouldBeRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_member'])) {
            return false;
        }

        $sql = $this->connection
            ->getDatabasePlatform()
            ->getListTableIndexesSQL('tl_member', $this->connection->getDatabase())
        ;

        $index = $this->connection->fetchAssoc($sql." AND INDEX_NAME = 'username'");

        return '0' !== $index['Non_Unique'];
    }

    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        $this->connection->query('ALTER TABLE `tl_member` CHANGE `username` `username` varchar(64) COLLATE utf8_bin NULL');
        $this->connection->query("UPDATE `tl_member` SET username=NULL WHERE username=''");
        $this->connection->query('ALTER TABLE `tl_member` DROP INDEX `username`, ADD UNIQUE KEY `username` (`username`)');
    }
}
