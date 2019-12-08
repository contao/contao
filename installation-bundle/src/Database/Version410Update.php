<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\Database;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class Version410Update extends AbstractMigration
{
    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getName(): string
    {
        return 'Contao 4.1.0 Update';
    }

    /**
     * {@inheritdoc}
     */
    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_user'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_user');

        return !isset($columns['imagesizes']);
    }

    /**
     * {@inheritdoc}
     */
    public function run(): MigrationResult
    {
        $crop = $GLOBALS['TL_CROP'];

        if (empty($crop)) {
            return $this->createResult();
        }

        $options = [];

        foreach ($crop as $modes) {
            $options[] = array_values($modes);
        }

        if (!empty($options)) {
            $options = array_merge(...$options);
        }

        $rows = $this->connection->fetchAll('
            SELECT
                id
            FROM
                tl_image_size
        ');

        foreach ($rows as $imageSize) {
            $options[] = $imageSize['id'];
        }

        // Add the database fields
        $this->connection->query('
            ALTER TABLE
                tl_user
            ADD
                imageSizes blob NULL
        ');

        $this->connection->query('
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

        $stmt->execute([':options' => serialize($options)]);

        return $this->createResult();
    }
}
