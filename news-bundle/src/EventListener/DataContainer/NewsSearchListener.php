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
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\Search;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
class NewsSearchListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ContentUrlGenerator $urlGenerator,
    ) {
    }

    #[AsCallback(table: 'tl_news', target: 'fields.alias.save')]
    public function onSaveAlias(string $value, DataContainer $dc): string
    {
        if ($value === ($dc->getCurrentRecord()['alias'] ?? null)) {
            return $value;
        }

        $this->purgeSearchIndex((int) $dc->id);

        return $value;
    }

    #[AsCallback(table: 'tl_news', target: 'fields.robots.save')]
    public function onSaveRobots(string $value, DataContainer $dc): string
    {
        if ($dc->getCurrentRecord()['robots'] === $value || str_starts_with($value, 'index')) {
            return $value;
        }

        if ('' === $value && str_starts_with($dc->getCurrentRecord()['robots'], 'index')) {
            // Get robots tag of the reader page (linked in news archive)
            $readerPageId = $this->framework->getAdapter(NewsArchiveModel::class)->findById($dc->getCurrentRecord()['pid'])->jumpTo ?? null;

            if ($readerPageId) {
                $readerPageRobots = $this->framework->getAdapter(PageModel::class)->findById($readerPageId)->robots ?? '';

                if (str_starts_with($readerPageRobots, 'index')) {
                    return $value;
                }
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
        $objNews = $this->framework->getAdapter(NewsModel::class)->findById($newsId);

        $newsUrl = $this->urlGenerator->generate($objNews, [], UrlGeneratorInterface::ABSOLUTE_URL);

        if ($newsUrl) {
            $search = $this->framework->getAdapter(Search::class);

            $search->removeEntry($newsUrl);
        }
    }
}
