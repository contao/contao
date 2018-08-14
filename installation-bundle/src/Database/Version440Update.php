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

use Contao\StringUtil;

class Version440Update extends AbstractVersionUpdate
{
    /**
     * {@inheritdoc}
     */
    public function shouldBeRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_content'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_content');

        return !isset($columns['imagetitle']);
    }

    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        // Add the js_autofocus.html5 template
        $statement = $this->connection->query('
            SELECT
                id, scripts
            FROM
                tl_layout
        ');

        while (false !== ($layout = $statement->fetch(\PDO::FETCH_OBJ))) {
            /** @var array $scripts */
            $scripts = StringUtil::deserialize($layout->scripts);

            if (!empty($scripts) && \is_array($scripts)) {
                $scripts[] = 'js_autofocus';

                $stmt = $this->connection->prepare('
                    UPDATE
                        tl_layout
                    SET
                        scripts = :scripts
                    WHERE
                        id = :id
                ');

                $stmt->execute([':scripts' => serialize(array_values(array_unique($scripts))), ':id' => $layout->id]);
            }
        }

        $schemaManager = $this->connection->getSchemaManager();

        if ($schemaManager->tablesExist(['tl_calendar_events'])) {
            $this->enableOverwriteMeta('tl_calendar_events');
        }

        if ($schemaManager->tablesExist(['tl_faq'])) {
            $this->enableOverwriteMeta('tl_faq');
        }

        if ($schemaManager->tablesExist(['tl_news'])) {
            $this->enableOverwriteMeta('tl_news');
        }

        $this->connection->query("
            ALTER TABLE
                tl_content
            CHANGE
                title imageTitle varchar(255) NOT NULL DEFAULT ''
        ");

        $this->connection->query("
            ALTER TABLE
                tl_content
            ADD
                overwriteMeta CHAR(1) DEFAULT '' NOT NULL
        ");

        $this->connection->query("
            UPDATE
                tl_content
            SET
                overwriteMeta = '1'
            WHERE
                alt != '' OR imageTitle != '' OR imageUrl != '' OR caption != ''
        ");
    }

    /**
     * Enables the "overwrite meta" field.
     *
     * @param string $table
     */
    private function enableOverwriteMeta(string $table): void
    {
        $this->connection->query("
            ALTER TABLE
                $table
            ADD
                overwriteMeta CHAR(1) DEFAULT '' NOT NULL
        ");

        $this->connection->query("
            UPDATE
                $table
            SET
                overwriteMeta = '1'
            WHERE
                alt != '' OR imageUrl != '' OR caption != ''
        ");
    }
}
