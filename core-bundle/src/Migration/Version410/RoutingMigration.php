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

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
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
    private Connection $connection;
    private ContaoFramework $framework;
    private string $urlSuffix;
    private bool $prependLocale;

    public function __construct(Connection $connection, ContaoFramework $framework, string $urlSuffix = '.html', bool $prependLocale = false)
    {
        $this->connection = $connection;
        $this->framework = $framework;
        $this->urlSuffix = $urlSuffix;
        $this->prependLocale = $prependLocale;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist('tl_page')) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_page');

        return !isset($columns['urlprefix']) && !isset($columns['urlsuffix']) && !isset($columns['usefolderurl']);
    }

    public function run(): MigrationResult
    {
        $urlPrefix = new Column('urlPrefix', new StringType());
        $urlPrefix->setColumnDefinition("varchar(128) BINARY NOT NULL default ''");

        $urlSuffix = new Column('urlSuffix', new StringType());
        $urlSuffix->setColumnDefinition("varchar(16) NOT NULL default '.html'");

        $useFolderUrl = new Column('useFolderUrl', new StringType());
        $useFolderUrl->setColumnDefinition("char(1) NOT NULL default ''");

        $diff = new TableDiff('tl_page', [$urlPrefix, $urlSuffix, $useFolderUrl]);
        $sql = $this->connection->getDatabasePlatform()->getAlterTableSQL($diff);

        foreach ($sql as $statement) {
            $this->connection->executeStatement($statement);
        }

        $prefix = $this->prependLocale ? 'language' : "''";

        $this->connection->executeStatement(
            "UPDATE tl_page SET urlPrefix=$prefix, urlSuffix=:suffix WHERE type='root'",
            ['suffix' => $this->urlSuffix]
        );

        $this->framework->initialize();

        $config = $this->framework->getAdapter(Config::class);

        if ($config->get('folderUrl')) {
            $this->connection->update('tl_page', ['useFolderUrl' => '1'], ['type' => 'root']);
        }

        return $this->createResult(true);
    }
}
