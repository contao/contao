<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version400;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class Version400Update extends AbstractMigration
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getName(): string
    {
        return 'Contao 4.0.0 Update';
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_layout'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_layout');

        return !isset($columns['scripts']);
    }

    public function run(): MigrationResult
    {
        $this->connection->query('
            ALTER TABLE
                tl_layout
            ADD
                scripts text NULL
        ');

        // Adjust the framework agnostic scripts
        $statement = $this->connection->query("
            SELECT
                id, addJQuery, jquery, addMooTools, mootools
            FROM
                tl_layout
            WHERE
                framework != ''
        ");

        while (false !== ($layout = $statement->fetch(\PDO::FETCH_OBJ))) {
            $scripts = [];

            // Check if j_slider is enabled
            if ($layout->addJQuery) {
                $jquery = StringUtil::deserialize($layout->jquery);

                if (!empty($jquery) && \is_array($jquery)) {
                    $key = array_search('j_slider', $jquery, true);

                    if (false !== $key) {
                        $scripts[] = 'js_slider';
                        unset($jquery[$key]);

                        $stmt = $this->connection->prepare('
                            UPDATE
                                tl_layout
                            SET
                                jquery = :jquery
                            WHERE
                                id = :id
                        ');

                        $stmt->execute([':jquery' => serialize(array_values($jquery)), ':id' => $layout->id]);
                    }
                }
            }

            // Check if moo_slider is enabled
            if ($layout->addMooTools) {
                $mootools = StringUtil::deserialize($layout->mootools);

                if (!empty($mootools) && \is_array($mootools)) {
                    $key = array_search('moo_slider', $mootools, true);

                    if (false !== $key) {
                        $scripts[] = 'js_slider';
                        unset($mootools[$key]);

                        $stmt = $this->connection->prepare('
                            UPDATE
                                tl_layout
                            SET
                                mootools = :mootools
                            WHERE
                                id = :id
                        ');

                        $stmt->execute([':mootools' => serialize(array_values($mootools)), ':id' => $layout->id]);
                    }
                }
            }

            // Enable the js_slider template
            if (!empty($scripts)) {
                $stmt = $this->connection->prepare('
                    UPDATE
                        tl_layout
                    SET
                        scripts = :scripts
                    WHERE
                        id = :id
                ');

                $stmt->execute([':scripts' => serialize(array_values($scripts)), ':id' => $layout->id]);
            }
        }

        // Replace moo_slimbox with moo_mediabox
        $statement = $this->connection->query("
            SELECT
                id, mootools
            FROM
                tl_layout
            WHERE
                framework != ''
        ");

        while (false !== ($layout = $statement->fetch(\PDO::FETCH_OBJ))) {
            $mootools = StringUtil::deserialize($layout->mootools);

            if (!empty($mootools) && \is_array($mootools)) {
                $key = array_search('moo_slimbox', $mootools, true);

                if (false !== $key) {
                    $mootools[] = 'moo_mediabox';
                    unset($mootools[$key]);

                    $stmt = $this->connection->prepare('
                        UPDATE
                            tl_layout
                        SET
                            mootools = :mootools
                        WHERE
                            id = :id
                    ');

                    $stmt->execute([':mootools' => serialize(array_values($mootools)), ':id' => $layout->id]);
                }
            }
        }

        // Adjust the list of framework style sheets
        $statement = $this->connection->query("
            SELECT
                id, framework
            FROM
                tl_layout
            WHERE
                framework != ''
        ");

        while (false !== ($layout = $statement->fetch(\PDO::FETCH_OBJ))) {
            $framework = StringUtil::deserialize($layout->framework);

            if (!empty($framework) && \is_array($framework)) {
                $key = array_search('tinymce.css', $framework, true);

                if (false !== $key) {
                    unset($framework[$key]);

                    $stmt = $this->connection->prepare('
                        UPDATE
                            tl_layout
                        SET
                            framework = :framework
                        WHERE
                            id = :id
                    ');

                    $stmt->execute([':framework' => serialize(array_values($framework)), ':id' => $layout->id]);
                }
            }
        }

        // Adjust the module types
        $this->connection->query("
            UPDATE
                tl_module
            SET
                type = 'articlelist'
            WHERE
                type = 'articleList'
        ");

        $this->connection->query("
            UPDATE
                tl_module
            SET
                type = 'rssReader'
            WHERE
                type = 'rss_reader'
        ");

        $this->connection->query("
            UPDATE
                tl_form_field
            SET
                type = 'explanation'
            WHERE
                type = 'headline'
        ");

        return $this->createResult(true);
    }
}
