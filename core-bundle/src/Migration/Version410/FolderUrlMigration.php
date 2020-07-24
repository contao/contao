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

/**
 * @internal
 */
class FolderUrlMigration extends AbstractMigration
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var Config
     */
    private $config;

    public function __construct(Connection $connection, ContaoFramework $framework)
    {
        $this->connection = $connection;
        $this->framework = $framework;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist('tl_page') || !$this->hasRootPages()) {
            return false;
        }

        if (!$this->getConfig()->has('folderUrl')) {
            return false;
        }
   
        return $this->getConfig()->get('folderUrl') && !$this->hasUpdatedRootPages();
    }

    public function run(): MigrationResult
    {
        $this->connection->update('tl_page', ['useFolderUrl' => '1'], ['type' => 'root']);

        $this->getConfig()->remove('folderUrl');
        $this->getConfig()->save();

        return $this->createResult(true);
    }

    private function getConfig(): Config
    {
        if (null !== $this->config) {
            return $this->config;
        }

        $this->framework->initialize();

        $this->config = $this->framework->createInstance(Config::class);

        return $this->config;
    }

    private function hasRootPages(): bool
    {
        $query = "SELECT COUNT(id) FROM tl_page WHERE ".$this->connection->quoteIdentifier('type')." = 'root'";
        return (int) $this->connection->executeQuery($query)->fetchColumn() > 0;
    }

    private function hasUpdatedRootPages(): bool
    {
        $query = "SELECT COUNT(id) FROM tl_page WHERE ".$this->connection->quoteIdentifier('type')." = 'root' AND useFolderUrl = '1'";
        return (int) $this->connection->executeQuery($query)->fetchColumn() > 0;
    }
}
