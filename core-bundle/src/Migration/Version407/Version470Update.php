<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version407;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * @internal
 */
class Version470Update extends AbstractMigration
{
    private Connection $connection;
    private Filesystem $filesystem;
    private string $uploadPath;
    private string $projectDir;

    public function __construct(Connection $connection, Filesystem $filesystem, string $uploadPath, string $projectDir)
    {
        $this->connection = $connection;
        $this->filesystem = $filesystem;
        $this->uploadPath = $uploadPath;
        $this->projectDir = $projectDir;
    }

    public function getName(): string
    {
        return 'Contao 4.7.0 Update';
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_layout'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_layout');

        return !isset($columns['minifymarkup']);
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement("
            ALTER TABLE
                tl_layout
            ADD
                minifyMarkup char(1) NOT NULL default ''
        ");

        // Enable the "minifyMarkup" option if it was enabled before
        if (isset($GLOBALS['TL_CONFIG']['minifyMarkup']) && $GLOBALS['TL_CONFIG']['minifyMarkup']) {
            $this->connection->executeStatement("
                UPDATE
                    tl_layout
                SET
                    minifyMarkup = '1'
            ");
        }

        // Add a .nosync file in every excluded folder
        if (!empty($GLOBALS['TL_CONFIG']['fileSyncExclude'])) {
            $folders = array_map('trim', explode(',', $GLOBALS['TL_CONFIG']['fileSyncExclude']));

            foreach ($folders as $folder) {
                if (is_dir($path = Path::join($this->projectDir, $this->uploadPath, $folder))) {
                    $this->filesystem->touch(Path::join($path, '.nosync'));
                }
            }
        }

        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['tl_comments_notify'])) {
            $this->connection->executeStatement("
                ALTER TABLE
                    tl_comments_notify
                ADD
                    active CHAR(1) DEFAULT '' NOT NULL
            ");

            $this->connection->executeStatement("
                UPDATE
                    tl_comments_notify
                SET
                    active = '1'
                WHERE
                    tokenConfirm = ''
            ");
        }

        return $this->createResult(true);
    }
}
