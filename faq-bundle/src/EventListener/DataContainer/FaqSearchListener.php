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
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\PageModel;
use Contao\Search;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
class FaqSearchListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ContentUrlGenerator $urlGenerator,
    ) {
    }

    #[AsCallback(table: 'tl_faq', target: 'fields.alias.save')]
    public function onSaveAlias(string $value, DataContainer $dc): string
    {
        if ($value === ($dc->getCurrentRecord()['alias'] ?? null)) {
            return $value;
        }

        $this->purgeSearchIndex((int) $dc->id);

        return $value;
    }

    #[AsCallback(table: 'tl_faq', target: 'fields.robots.save')]
    public function onSaveRobots(string $value, DataContainer $dc): string
    {
        if ($dc->getCurrentRecord()['robots'] === $value || str_starts_with($value, 'index')) {
            return $value;
        }

        if ('' === $value && str_starts_with($dc->getCurrentRecord()['robots'], 'index')) {
            // Get robots tag of the reader page (linked in FAQ category)
            $readerPageId = $this->framework->getAdapter(FaqCategoryModel::class)->findById($dc->getCurrentRecord()['pid'])->jumpTo ?? null;

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
        $objFaq = $this->framework->getAdapter(FaqModel::class)->findById($faqId);

        $faqUrl = $this->urlGenerator->generate($objFaq, [], UrlGeneratorInterface::ABSOLUTE_URL);

        if ($faqUrl) {
            $search = $this->framework->getAdapter(Search::class);

            $search->removeEntry($faqUrl);
        }
    }
}
