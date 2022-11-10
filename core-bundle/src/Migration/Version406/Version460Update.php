<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version406;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class Version460Update extends AbstractMigration
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getName(): string
    {
        return 'Contao 4.6.0 Update';
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_content'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_content');

        return !isset($columns['playeroptions']);
    }

    public function run(): MigrationResult
    {
        // Convert 403 pages to 401 pages so the login redirect does not break
        $this->connection->executeStatement("
            UPDATE
                tl_page
            SET
                type = 'error_401'
            WHERE
                type = 'error_403'
        ");

        // Adjust the search module settings (see contao/core-bundle#1462)
        $this->connection->executeStatement("
            UPDATE
                tl_module
            SET
                pages = CONCAT('a:1:{i:0;i:', rootPage, ';}'),
                rootPage = 0
            WHERE
                type = 'search' AND rootPage != 0
        ");

        // Activate the "overwriteLink" option (see contao/core-bundle#1459)
        $this->connection->executeStatement("
            ALTER TABLE
                tl_content
            ADD
                overwriteLink CHAR(1) DEFAULT '' NOT NULL
        ");

        $this->connection->executeStatement("
            UPDATE
                tl_content
            SET
                overwriteLink = '1'
            WHERE
                linkTitle != '' OR titleText != ''
        ");

        // Revert the incorrect version 2.8 update changes
        $this->connection->executeStatement('
            UPDATE
                tl_member
            SET
                currentLogin = 0
            WHERE
                currentLogin > 0 AND currentLogin = dateAdded
        ');

        // Remove all activation tokens older than one day to prevent accidental
        // deletion of existing member accounts
        $this->connection->executeStatement(
            "UPDATE tl_member SET activation = '' WHERE activation != '' AND dateAdded < :dateAdded",
            ['dateAdded' => strtotime('-1 day')]
        );

        // Update the video element settings (see contao/core-bundle#1348)
        $this->connection->executeStatement('
            ALTER TABLE
                tl_content
            ADD
                playerOptions text NULL
        ');

        $this->connection->executeStatement('
            ALTER TABLE
                tl_content
            ADD
                vimeoOptions text NULL
        ');

        $elements = $this->connection->fetchAllAssociative("
            SELECT
                id, type, youtubeOptions
            FROM
                tl_content
            WHERE
                autoplay = '1'
        ");

        foreach ($elements as $element) {
            switch ($element['type']) {
                case 'player':
                    $this->connection->executeStatement(
                        'UPDATE tl_content SET playerOptions = :options WHERE id = :id',
                        ['options' => serialize(['player_autoplay']), 'id' => $element['id']]
                    );
                    break;

                case 'youtube':
                    $options = StringUtil::deserialize($element['youtubeOptions']);
                    $options[] = 'youtube_autoplay';

                    $this->connection->executeStatement(
                        'UPDATE tl_content SET youtubeOptions = :options WHERE id = :id',
                        ['options' => serialize($options), 'id' => $element['id']]
                    );
                    break;

                case 'vimeo':
                    $this->connection->executeStatement(
                        'UPDATE tl_content SET vimeoOptions = :options WHERE id = :id',
                        ['options' => serialize(['vimeo_autoplay']), 'id' => $element['id']]
                    );
                    break;
            }
        }

        $this->connection->executeStatement('
            ALTER TABLE
                tl_content
            ADD
                playerStart int(10) unsigned NOT NULL default 0
        ');

        $this->connection->executeStatement('UPDATE tl_content SET playerStart = youtubeStart');

        $this->connection->executeStatement('
            ALTER TABLE
                tl_content
            ADD
                playerStop int(10) unsigned NOT NULL default 0
        ');

        $this->connection->executeStatement('UPDATE tl_content SET playerStop = youtubeStop');

        return $this->createResult(true);
    }
}
