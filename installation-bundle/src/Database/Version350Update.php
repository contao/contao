<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\Database;

/**
 * Runs the version 3.5.0 update.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class Version350Update extends AbstractVersionUpdate
{
    /**
     * {@inheritdoc}
     */
    public function shouldBeRun()
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
    public function run()
    {
        $this->connection->query('
            ALTER TABLE
                tl_member
            CHANGE
                username username varchar(64) COLLATE utf8_bin NULL
        ');

        $this->connection->query("
            UPDATE
                tl_member
            SET
                username = NULL
            WHERE
                username = ''
        ");

        $this->connection->query('
            ALTER TABLE
                tl_member
            DROP INDEX
                username,
            ADD UNIQUE KEY
                username (username)
        ');
    }
}
