<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version401;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\ImageSizes;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class Version410Update extends AbstractMigration
{
    public function __construct(private Connection $connection, private ContaoFramework $framework, private ImageSizes $imageSizes)
    {
    }

    public function getName(): string
    {
        return 'Contao 4.1.0 Update';
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_user', 'tl_user_group', 'tl_image_size'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_user');

        return !isset($columns['imagesizes']);
    }

    public function run(): MigrationResult
    {
        $this->framework->initialize();

        $options = [];

        foreach ($this->imageSizes->getAllOptions() as $modes) {
            $options[] = array_values($modes);
        }

        if (!empty($options)) {
            $options = array_merge(...$options);
        }

        // Add the database fields
        $this->connection->executeStatement('
            ALTER TABLE
                tl_user
            ADD
                imageSizes blob NULL
        ');

        $this->connection->executeStatement('
            ALTER TABLE
                tl_user_group
            ADD
                imageSizes blob NULL
        ');

        // Grant access to all existing image sizes at group level
        $stmt = $this->connection->prepare('
            UPDATE
                tl_user_group
            SET
                imageSizes = :options
        ');

        $stmt->executeStatement([':options' => serialize($options)]);

        return $this->createResult(true);
    }
}
