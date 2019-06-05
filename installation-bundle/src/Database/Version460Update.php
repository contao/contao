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
        // Convert 403 pages to 401 pages so the login redirect does not break
        $this->connection->query("
            UPDATE
                tl_page
            SET
                type = 'error_401'
            WHERE
                type = 'error_403'
        ");

        // Adjust the search module settings (see contao/core-bundle#1462)
        $this->connection->query("
            UPDATE
                tl_module
            SET
                pages = CONCAT('a:1:{i:0;i:', rootPage, ';}'),
                rootPage = 0
            WHERE
                type = 'search' AND rootPage != 0
        ");

        // Activate the "overwriteLink" option (see contao/core-bundle#1459)
        $this->connection->query("
            ALTER TABLE
                tl_content
            ADD
                overwriteLink CHAR(1) DEFAULT '' NOT NULL
        ");

        $this->connection->query("
            UPDATE
                tl_content
            SET
                overwriteLink = '1'
            WHERE
                linkTitle != '' OR titleText != ''
        ");

        // Revert the incorrect version 2.8 update changes
        $this->connection->query('
            UPDATE
                tl_member
            SET
                currentLogin = 0
            WHERE
                currentLogin > 0 AND currentLogin = dateAdded
        ');

        // Remove all activation tokens older than one day to prevent accidental
        // deletion of existing member accounts
        $stmt = $this->connection->prepare("
            UPDATE
                tl_member
            SET
                activation = ''
            WHERE
                activation != '' AND dateAdded < :dateAdded
        ");

        $stmt->execute([':dateAdded' => strtotime('-1 day')]);

        // Update the video element settings (see contao/core-bundle#1348)
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

        $this->connection->query('
            ALTER TABLE
                tl_content
            ADD
                playerStart int(10) unsigned NOT NULL default 0
        ');

        $this->connection->query('UPDATE tl_content SET playerStart = youtubeStart');

        $this->connection->query('
            ALTER TABLE
                tl_content
            ADD
                playerStop int(10) unsigned NOT NULL default 0
        ');

        $this->connection->query('UPDATE tl_content SET playerStop = youtubeStop');
    }
}
