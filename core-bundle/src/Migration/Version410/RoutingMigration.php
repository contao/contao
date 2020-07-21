<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version410;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\StringType;

/**
 * @internal
 */
class RoutingMigration extends AbstractMigration
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $urlSuffix;

    /**
     * @var bool
     */
    private $prependLocale;

    public function __construct(Connection $connection, string $urlSuffix = '.html', bool $prependLocale = false)
    {
        $this->connection = $connection;
        $this->urlSuffix = $urlSuffix;
        $this->prependLocale = $prependLocale;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist('tl_page')) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_page');

        return !isset($columns['urlprefix']) && !isset($columns['urlsuffix']);
    }

    public function run(): MigrationResult
    {
        $urlPrefix = new Column('urlPrefix', new StringType());
        $urlPrefix->setColumnDefinition("varchar(128) BINARY NOT NULL default ''");

        $urlSuffix = new Column('urlSuffix', new StringType());
        $urlSuffix->setColumnDefinition("varchar(16) NOT NULL default '.html'");

        $diff = new TableDiff('tl_page', [$urlPrefix, $urlSuffix]);
        $sql = $this->connection->getDatabasePlatform()->getAlterTableSQL($diff);

        foreach ($sql as $statement) {
            $this->connection->exec($statement);
        }

        $prefix = $this->prependLocale ? 'language' : "''";

        $this->connection
            ->prepare("UPDATE tl_page SET urlPrefix=$prefix, urlSuffix=:suffix WHERE type='root'")
            ->execute(['suffix' => $this->urlSuffix])
        ;

        return $this->createResult(true);
    }
}
