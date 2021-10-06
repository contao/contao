<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version405;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class Version450Update extends AbstractMigration
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getName(): string
    {
        return 'Contao 4.5.0 Update';
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_layout'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_layout');

        return !isset($columns['externaljs']);
    }

    public function run(): MigrationResult
    {
        $columns = $this->connection
            ->getSchemaManager()
            ->listTableColumns('tl_content')
        ;

        if (!isset($columns['youtubeoptions'])) {
            $this->connection->query('
                ALTER TABLE
                    tl_content
                ADD
                    youtubeOptions text NULL
            ');
        }

        if (!isset($columns['youtubestart'])) {
            $this->connection->query('
                ALTER TABLE
                    tl_content
                ADD
                    youtubeStart int(10) unsigned NOT NULL default 0
            ');
        }

        if (!isset($columns['youtubestop'])) {
            $this->connection->query('
                ALTER TABLE
                    tl_content
                ADD
                    youtubeStop int(10) unsigned NOT NULL default 0
            ');
        }

        $columns = $this->connection
            ->getSchemaManager()
            ->listTableColumns('tl_form_field')
        ;

        if (isset($columns['fstype'])) {
            $this->connection->query("
                UPDATE
                    tl_form_field
                SET
                    type = 'fieldsetStart'
                WHERE
                    type = 'fieldset' AND fsType = 'fsStart'
            ");

            $this->connection->query("
                UPDATE
                    tl_form_field
                SET
                    type = 'fieldsetStop'
                WHERE
                    type = 'fieldset' AND fsType = 'fsStop'
            ");
        }

        $columns = $this->connection
            ->getSchemaManager()
            ->listTableColumns('tl_module')
        ;

        if (isset($columns['news_order'])) {
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
        }

        $this->connection->query('
            ALTER TABLE
                tl_layout
            ADD
                externalJs BLOB DEFAULT NULL
        ');

        return $this->createResult(true);
    }
}
