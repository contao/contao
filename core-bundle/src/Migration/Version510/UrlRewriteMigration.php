<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version510;

use Contao\Controller;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Migrates data from the terminal42/contao-url-rewrite bundle
 */
class UrlRewriteMigration extends AbstractMigration
{
    public function __construct(private Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist('tl_url_rewrite')) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_url_rewrite');

        return (\array_key_exists('inactive', $columns) && !\array_key_exists('disable', $columns))
            || (\array_key_exists('requestHosts', $columns) && !\array_key_exists('requestHost', $columns));
    }

    public function run(): MigrationResult
    {
        $columns = $this->connection->createSchemaManager()->listTableColumns('tl_url_rewrite');

        if (\array_key_exists('inactive', $columns) && !\array_key_exists('disable', $columns)) {
            $this->connection->executeStatement('ALTER TABLE tl_url_rewrite CHANGE `inactive` `disable` bool DEFAULT 0');
        }

        if (\array_key_exists('requestHosts', $columns) && !\array_key_exists('requestHost', $columns)) {
            $this->connection->executeStatement("ALTER TABLE tl_url_rewrite ADD `requestHost` varchar(255) NOT NULL default ''");

            $rules = $this->connection->executeQuery("SELECT * FROM tl_url_rewrite WHERE requestHosts IS NOT NULL AND requestHosts != 'a:1:{i:0;s:0:\"\";}'");
            foreach ($rules->iterateAssociative() as $rule) {
                $hosts = array_values(StringUtil::deserialize($rule['requestHosts'], true));

                if (\count($hosts) < 2) {
                    $this->connection->executeStatement(
                        'UPDATE tl_url_rewrite SET requestHost=? WHERE id=?',
                        [$hosts[0] ?? ''],
                        $rule['id']
                    );

                    continue;
                }

                foreach ($hosts as $host) {
                    $data = $rule;
                    unset($data['id']);
                    $data['requestHost'] = $host;

                    $this->connection->insert('tl_url_rewrite', $data);
                }

                $this->connection->delete('tl_url_rewrite', ['id' => $rule['id']]);
            }
        }

        return $this->createResult(true);
    }
}
