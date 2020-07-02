<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Migration\Version410;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class DenylistMigration extends AbstractMigration
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        return $schemaManager->tablesExist('tl_newsletter_blacklist')
            && !$schemaManager->tablesExist('tl_newsletter_denylist');
    }

    public function run(): MigrationResult
    {
        $schemaManager = $this->connection->getSchemaManager();
        $schemaManager->renameTable('tl_newsletter_blacklist', 'tl_newsletter_denylist');

        return $this->createResult(true);
    }
}
