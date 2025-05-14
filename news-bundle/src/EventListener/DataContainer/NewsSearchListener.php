<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\DataContainer;
use Contao\NewsModel;
use Contao\Search;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
class NewsSearchListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly ContentUrlGenerator $urlGenerator,
    ) {
    }

    #[AsCallback(table: 'tl_news', target: 'fields.alias.save')]
    public function onSaveAlias(string $value, DataContainer $dc): string
    {
        if (($dc->getCurrentRecord()['alias'] ?? null) === $value) {
            return $value;
        }

        $this->purgeSearchIndex((int) $dc->id);

        return $value;
    }

    #[AsCallback(table: 'tl_news', target: 'fields.robots.save')]
    public function onSaveRobots(string $value, DataContainer $dc): string
    {
        if (($dc->getCurrentRecord()['robots'] ?? null) === $value || str_starts_with($value, 'index')) {
            return $value;
        }

        if ('' === $value && str_starts_with($dc->getCurrentRecord()['robots'] ?? '', 'index')) {
            // Get the robots tag of the reader page (linked in news archive)
            $readerPageRobots = $this->connection->fetchOne(
                <<<'SQL'
                    SELECT p.robots
                    FROM tl_page AS p, tl_news_archive AS c
                    WHERE c.id = ? AND c.jumpTo = p.id
                    SQL,
                [$dc->getCurrentRecord()['pid']],
            );

            if (str_starts_with((string) $readerPageRobots, 'index')) {
                return $value;
            }
        }

        $this->purgeSearchIndex((int) $dc->id);

        return $value;
    }

    #[AsCallback(table: 'tl_news', target: 'config.ondelete', priority: 16)]
    public function onDelete(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        $this->purgeSearchIndex((int) $dc->id);
    }

    private function purgeSearchIndex(int $newsId): void
    {
        $news = $this->framework->getAdapter(NewsModel::class)->findById($newsId);

        try {
            $newsUrl = $this->urlGenerator->generate($news, [], UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (ExceptionInterface) {
            return;
        }

        $search = $this->framework->getAdapter(Search::class);
        $search->removeEntry($newsUrl);
    }
}
