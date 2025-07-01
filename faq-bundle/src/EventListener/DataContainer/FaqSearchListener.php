<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\DataContainer;
use Contao\FaqModel;
use Contao\Search;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
class FaqSearchListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly ContentUrlGenerator $urlGenerator,
    ) {
    }

    #[AsCallback(table: 'tl_faq', target: 'fields.alias.save')]
    public function onSaveAlias(string $value, DataContainer $dc): string
    {
        if (($dc->getCurrentRecord()['alias'] ?? null) === $value) {
            return $value;
        }

        $this->purgeSearchIndex((int) $dc->id);

        return $value;
    }

    #[AsCallback(table: 'tl_faq', target: 'fields.searchIndexer.save')]
    public function onSaveSearchIndexer(string $value, DataContainer $dc): string
    {
        if ('always_index' === $value || ($dc->getCurrentRecord()['searchIndexer'] ?? null) === $value) {
            return $value;
        }

        if (!$value) {
            // Get robots and searchIndexer of the reader page (linked in calendar)
            $readerPageSettings = $this->connection->fetchAssociative(
                <<<'SQL'
                    SELECT
                        p.robots,
                        p.searchIndexer
                    FROM
                        tl_page AS p,
                        tl_faq_category AS c
                    WHERE
                        c.id = ?
                        AND c.jumpTo = p.id
                    SQL,
                [$dc->getCurrentRecord()['pid']],
            );

            $readerSearchIndexer = (string) ($readerPageSettings['searchIndexer'] ?? null);
            $readerRobots = (string) ($readerPageSettings['robots'] ?? null);
            $entryRobots = (string) ($dc->getCurrentRecord()['robots'] ?? null);

            if ('always_index' === $readerSearchIndexer || (!$readerSearchIndexer && (str_starts_with($entryRobots, 'index') || (!$entryRobots && str_starts_with($readerRobots, 'index'))))) {
                return $value;
            }
        }

        $this->purgeSearchIndex((int) $dc->id);

        return $value;
    }

    #[AsCallback(table: 'tl_faq', target: 'fields.robots.save')]
    public function onSaveRobots(string $value, DataContainer $dc): string
    {
        if (($dc->getCurrentRecord()['robots'] ?? null) === $value || 'always_index' === $dc->getCurrentRecord()['searchIndexer']) {
            return $value;
        }

        if (!$dc->getCurrentRecord()['searchIndexer']) {
            // Get robots and searchIndexer of the reader page (linked in calendar)
            $readerPageSettings = $this->connection->fetchAssociative(
                <<<'SQL'
                    SELECT
                        p.robots,
                        p.searchIndexer
                    FROM
                        tl_page AS p,
                        tl_faq_category AS c
                    WHERE
                        c.id = ?
                        AND c.jumpTo = p.id
                    SQL,
                [$dc->getCurrentRecord()['pid']],
            );

            $readerSearchIndexer = (string) ($readerPageSettings['searchIndexer'] ?? null);
            $readerRobots = (string) ($readerPageSettings['robots'] ?? null);

            if ('always_index' === $readerSearchIndexer || (!$readerSearchIndexer && (str_starts_with($value, 'index') || (!$value && str_starts_with($readerRobots, 'index'))))) {
                return $value;
            }
        }

        $this->purgeSearchIndex((int) $dc->id);

        return $value;
    }

    #[AsCallback(table: 'tl_faq', target: 'config.ondelete', priority: 16)]
    public function onDelete(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        $this->purgeSearchIndex((int) $dc->id);
    }

    private function purgeSearchIndex(int $faqId): void
    {
        $faq = $this->framework->getAdapter(FaqModel::class)->findById($faqId);

        try {
            $faqUrl = $this->urlGenerator->generate($faq, [], UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (ExceptionInterface) {
            return;
        }

        $search = $this->framework->getAdapter(Search::class);
        $search->removeEntry($faqUrl);
    }
}
