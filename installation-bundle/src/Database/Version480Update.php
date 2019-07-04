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

use Contao\File;
use Contao\StringUtil;

class Version480Update extends AbstractVersionUpdate
{
    /**
     * {@inheritdoc}
     */
    public function shouldBeRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_layout'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_layout');

        return isset($columns['picturefill']);
    }

    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        $this->connection->query('
            ALTER TABLE
                tl_layout
            DROP
                picturefill
        ');

        $statement = $this->connection->query('
            SELECT
                id, jquery, scripts
            FROM
                tl_layout
        ');

        // Remove the "j_mediaelement" and "js_mediaelement" templates
        while (false !== ($row = $statement->fetch(\PDO::FETCH_OBJ))) {
            if ($row->jquery) {
                $jquery = StringUtil::deserialize($row->jquery);

                if (\is_array($jquery) && false !== ($i = array_search('j_mediaelement', $jquery, true))) {
                    unset($jquery[$i]);

                    $stmt = $this->connection->prepare('
                        UPDATE
                            tl_layout
                        SET
                            jquery = :jquery
                        WHERE
                            id = :id
                    ');

                    $stmt->execute([':jquery' => serialize(array_values($jquery)), ':id' => $row->id]);
                }
            }

            if ($row->scripts) {
                $scripts = StringUtil::deserialize($row->scripts);

                if (\is_array($scripts) && false !== ($i = array_search('js_mediaelement', $scripts, true))) {
                    unset($scripts[$i]);

                    $stmt = $this->connection->prepare('
                        UPDATE
                            tl_layout
                        SET
                            scripts = :scripts
                        WHERE
                            id = :id
                    ');

                    $stmt->execute([':scripts' => serialize(array_values($scripts)), ':id' => $row->id]);
                }
            }
        }

        $this->connection->query("
            ALTER TABLE
                tl_image_size
            ADD
                skipIfDimensionsMatch char(1) NOT NULL default ''
        ");

        // Enable the "skipIfDimensionsMatch" option for existing image sizes (backwards compatibility)
        $this->connection->query("
            UPDATE
                tl_image_size
            SET
                skipIfDimensionsMatch = '1'
        ");

        $this->connection->query('
            ALTER TABLE
                tl_files
            CHANGE
                importantPartX importantPartX DOUBLE PRECISION DEFAULT 0 NOT NULL,
            CHANGE
                importantPartY importantPartY DOUBLE PRECISION DEFAULT 0 NOT NULL,
            CHANGE
                importantPartWidth importantPartWidth DOUBLE PRECISION DEFAULT 0 NOT NULL,
            CHANGE
                importantPartHeight importantPartHeight DOUBLE PRECISION DEFAULT 0 NOT NULL
        ');

        $statement = $this->connection->query('
            SELECT
                id, path, importantPartX, importantPartY, importantPartWidth, importantPartHeight
            FROM
                tl_files
            WHERE
                importantPartWidth > 0 OR importantPartHeight > 0
        ');

        $rootDir = $this->container->getParameter('kernel.project_dir');

        // Convert the important part to relative values as fractions
        while (false !== ($file = $statement->fetch(\PDO::FETCH_OBJ))) {
            if (!file_exists($rootDir.'/'.$file->path) || is_dir($rootDir.'/'.$file->path)) {
                continue;
            }

            $imageSize = (new File($file->path))->imageViewSize;

            if (empty($imageSize[0]) || empty($imageSize[1])) {
                continue;
            }

            $stmt = $this->connection->prepare('
                UPDATE
                    tl_files
                SET
                    importantPartX = :x,
                    importantPartY = :y,
                    importantPartWidth = :width,
                    importantPartHeight = :height
                WHERE
                    id = :id
            ');

            $stmt->execute([
                ':id' => $file->id,
                ':x' => $file->importantPartX / $imageSize[0],
                ':y' => $file->importantPartY / $imageSize[1],
                ':width' => $file->importantPartWidth / $imageSize[0],
                ':height' => $file->importantPartHeight / $imageSize[1],
            ]);
        }

        $this->connection->query('
            ALTER TABLE
                tl_module
            ADD
                minKeywordLength smallint(5) unsigned NOT NULL default 4
            AFTER
                contextLength
        ');

        // Disable the minimum keyword length for existing modules (backwards compatibility)
        $this->connection->query("
            UPDATE
                tl_module
            SET
                minKeywordLength = 0
            WHERE
                type = 'search'
        ");

        $this->connection->query("
            ALTER TABLE
                tl_module
            CHANGE
                contextLength contextLength varchar(64) NOT NULL default ''
        ");

        $statement = $this->connection->query("
            SELECT
                id, contextLength, totalLength
            FROM
                tl_module
            WHERE
                type = 'search'
        ");

        // Consolidate the search context fields
        while (false !== ($row = $statement->fetch(\PDO::FETCH_OBJ))) {
            $stmt = $this->connection->prepare('
                UPDATE
                    tl_module
                SET
                    contextLength = :context
                WHERE
                    id = :id
            ');

            $stmt->execute([
                ':id' => $row->id,
                ':context' => serialize([$row->contextLength, $row->totalLength]),
            ]);
        }
    }
}
