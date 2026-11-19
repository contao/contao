<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version503;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\DropZone;
use Contao\FileUpload;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class UploaderMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_user'])) {
            return false;
        }

        if (!isset($schemaManager->listTableColumns('tl_user')['uploader'])) {
            return false;
        }

        return false !== $this->connection->fetchOne("SELECT TRUE FROM tl_user WHERE uploader IN ('DropZone', 'FileUpload') LIMIT 1");
    }

    public function run(): MigrationResult
    {
        foreach (['DropZone' => DropZone::class, 'FileUpload' => FileUpload::class] as $old => $new) {
            $this->connection->update('tl_user', ['uploader' => $new], ['uploader' => $old]);
        }

        return $this->createResult(true);
    }
}
