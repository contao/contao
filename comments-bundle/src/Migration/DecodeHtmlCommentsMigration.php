<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CommentsBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\CoreBundle\String\HtmlDecoder;
use Doctrine\DBAL\Connection;

class DecodeHtmlCommentsMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection,
        private readonly HtmlDecoder $htmlDecoder,
    ) {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (
            !$schemaManager->tablesExist(['tl_module', 'tl_comments'])
            || !\array_key_exists('com_bbcode', $schemaManager->listTableColumns('tl_module'))
            || !\array_key_exists('comment', $schemaManager->listTableColumns('tl_comments'))
        ) {
            return false;
        }

        $count = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_comments WHERE comment != \'\'');

        return $count > 0;
    }

    public function run(): MigrationResult
    {
        $ids = [];
        $comments = $this->connection->fetchAllKeyValue('SELECT id, comment FROM tl_comments WHERE comment != \'\'');

        foreach ($comments as $id => $comment) {
            $commentDecoded = $this->htmlDecoder->htmlToPlainText($comment, true);

            if ($comment === $commentDecoded) {
                continue;
            }

            $ids[] = $id;

            $this->connection->update('tl_comments', ['comment' => $commentDecoded], ['id' => (int) $id]);
        }

        $this->connection->executeStatement('ALTER TABLE tl_module DROP COLUMN com_bbcode');

        return $this->createResult(true, "{$this->getName()} executed successfully: ".implode(', ', $ids));
    }
}
