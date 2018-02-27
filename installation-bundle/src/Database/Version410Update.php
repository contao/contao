<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\Database;

/**
 * Runs the version 4.1.0 update.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class Version410Update extends AbstractVersionUpdate
{
    /**
     * {@inheritdoc}
     */
    public function shouldBeRun()
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
    public function run()
    {
        $crop = $GLOBALS['TL_CROP'];

        if (empty($crop)) {
            return;
        }

        $options = [];

        foreach ($crop as $group => $values) {
            $options = array_merge($options, array_values($values));
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
    }
}
