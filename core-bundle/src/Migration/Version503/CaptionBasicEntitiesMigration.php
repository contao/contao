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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class CaptionBasicEntitiesMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
    ) {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_files'])) {
            return false;
        }

        if (!$records = $this->getAffectedRecords()) {
            return false;
        }

        $this->framework->initialize();

        foreach ($records as $meta) {
            $meta = StringUtil::deserialize($meta, true);

            foreach ($meta as $data) {
                if (!($data['caption'] ?? null)) {
                    continue;
                }

                if ($data['caption'] !== StringUtil::restoreBasicEntities($data['caption'])) {
                    return true;
                }
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        foreach ($this->getAffectedRecords() as $id => $meta) {
            $meta = StringUtil::deserialize($meta, true);

            foreach ($meta as $lang => $data) {
                if (!($data['caption'] ?? null)) {
                    continue;
                }

                $meta[$lang]['caption'] = StringUtil::restoreBasicEntities($data['caption']);
            }

            $this->connection->update('tl_files', ['meta' => serialize($meta)], ['id' => $id]);
        }

        return $this->createResult(true);
    }

    private function getAffectedRecords(): array
    {
        return $this->connection->fetchAllKeyValue("
                SELECT id, meta
                FROM tl_files
                WHERE meta IS NOT NULL AND CAST(`meta` AS BINARY) REGEXP CAST('\\\\[(&|&amp;|lt|gt|nbsp|-)\\\\]' AS BINARY)
            ");
    }
}
