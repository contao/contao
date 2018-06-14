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

class Version460Update extends AbstractVersionUpdate
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

        return !isset($columns['playeroptions']);
    }

    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        $this->connection->query("
            UPDATE
                tl_module
            SET
                pages = CONCAT('a:1:{i:0;i:', rootPage, ';}'),
                rootPage = 0
            WHERE
                type = 'search' AND rootPage != 0
        ");

        $this->connection->query('
            ALTER TABLE
                tl_content
            ADD
                playerOptions text NULL
        ');

        $this->connection->query('
            ALTER TABLE
                tl_content
            ADD
                vimeoOptions text NULL
        ');

        $statement = $this->connection->query("
            SELECT
                id, type, youtubeOptions
            FROM
                tl_content
            WHERE
                autoplay = '1'
        ");

        while (false !== ($element = $statement->fetch(\PDO::FETCH_OBJ))) {
            switch ($element->type) {
                case 'player':
                    $stmt = $this->connection->prepare('
                        UPDATE
                            tl_content
                        SET
                            playerOptions = :options
                        WHERE
                            id = :id
                    ');

                    $stmt->execute([':options' => serialize(['player_autoplay']), ':id' => $element->id]);
                    break;

                case 'youtube':
                    /** @var array $options */
                    $options = StringUtil::deserialize($element->youtubeOptions);
                    $options[] = 'youtube_autoplay';

                    $stmt = $this->connection->prepare('
                        UPDATE
                            tl_content
                        SET
                            youtubeOptions = :options
                        WHERE
                            id = :id
                    ');

                    $stmt->execute([':options' => serialize($options), ':id' => $element->id]);
                    break;

                case 'vimeo':
                    $stmt = $this->connection->prepare('
                        UPDATE
                            tl_content
                        SET
                            vimeoOptions = :options
                        WHERE
                            id = :id
                    ');

                    $stmt->execute([':options' => serialize(['vimeo_autoplay']), ':id' => $element->id]);
                    break;
            }
        }

        $this->connection->query("
            ALTER TABLE
                tl_content
            ADD
                playerStart int(10) unsigned NOT NULL default '0'
        ");

        $this->connection->query('UPDATE tl_content SET playerStart = youtubeStart');

        $this->connection->query("
            ALTER TABLE
                tl_content
            ADD
                playerStop int(10) unsigned NOT NULL default '0'
        ");

        $this->connection->query('UPDATE tl_content SET playerStop = youtubeStop');
    }
}
