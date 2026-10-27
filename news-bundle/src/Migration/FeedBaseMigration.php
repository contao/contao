<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Migration;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Nyholm\Psr7\Uri;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Populates empty tl_page.feedBase entries with the base URL of its first news
 * archive. If the URL of a news archive cannot be determined, this migration will
 * run again on the next execution of contao:migrate.
 *
 * @internal
 */
class FeedBaseMigration extends AbstractMigration
{
    /**
     * @var list<int>
     */
    private array $processed = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly ContentUrlGenerator $urlGenerator,
    ) {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_page', 'tl_news_archive'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_page');

        if (!isset($columns['feedbase'], $columns['newsarchives'])) {
            return false;
        }

        $ids = $this->connection->fetchFirstColumn("SELECT id FROM tl_page WHERE type = 'news_feed' AND feedBase = ''");

        return [] !== array_diff($ids, $this->processed);
    }

    public function run(): MigrationResult
    {
        $feeds = $this->connection->fetchAllKeyValue("SELECT id, newsArchives FROM tl_page WHERE type = 'news_feed' AND feedBase = ''");

        $this->framework->initialize();

        $pageAdapter = $this->framework->getAdapter(PageModel::class);

        foreach ($feeds as $id => $archives) {
            foreach (StringUtil::deserialize($archives, true) as $archiveId) {
                $jumpTo = $this->connection->fetchOne('SELECT jumpTo FROM tl_news_archive WHERE id = ?', [$archiveId]);

                if (!$jumpTo || !$page = $pageAdapter->findById($jumpTo)) {
                    continue;
                }

                try {
                    // Use a dummy parameter for when requireItem is enabled
                    $uri = new Uri($this->urlGenerator->generate($page, ['parameters' => '/foo'], UrlGeneratorInterface::ABSOLUTE_URL));
                } catch (ExceptionInterface) {
                    continue;
                }

                if ('' === $uri->getScheme() || '' === $uri->getHost()) {
                    continue;
                }

                $this->connection->update('tl_page', ['feedBase' => $uri->getScheme().'://'.$uri->getHost()], ['id' => (int) $id]);

                break;
            }
        }

        $this->processed = array_keys($feeds);

        return $this->createResult(true);
    }
}
