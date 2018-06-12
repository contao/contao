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

class Version460Update extends AbstractVersionUpdate
{
    /**
     * {@inheritdoc}
     */
    public function shouldBeRun(): bool
    {
        $statement = $this->connection->query("
            SELECT
                id
            FROM
                tl_module
            WHERE
                type = 'search' AND rootPage != '0'
        ");

        return false !== $statement->fetch();
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
                pages = CONCAT('a:1:{i:0;i:', rootPage, ';}'),
                rootPage = 0
            WHERE
                type = 'search' AND rootPage != '0'
        ");
    }
}
