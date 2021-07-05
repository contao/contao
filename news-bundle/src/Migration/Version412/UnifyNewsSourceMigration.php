<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Migration\Version412;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class UnifyNewsSourceMigration extends AbstractMigration
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist('tl_news')) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_news');

        return isset($columns['articleid'], $columns['jumpto']);
    }

    public function run(): MigrationResult
    {
        $news = $this->connection->fetchAllAssociative(
            "SELECT id, source, articleId, jumpTo FROM tl_news WHERE source IN ('internal', 'article')"
        );

        foreach ($news as $old) {
            $update = [
                'source' => 'external',
                'target' => '',
                'url' => 'internal' === $old['source'] ?
                    sprintf('{{link_url::%d}}', $old['jumpTo']) :
                    sprintf('{{article_url::%d}}', $old['articleId']),
            ];

            $this->connection->update('tl_news', $update, ['id' => $old['id']]);
        }

        $this->connection->executeQuery('ALTER TABLE tl_news DROP COLUMN articleId, DROP COLUMN jumpTo');

        return $this->createResult(true);
    }
}
