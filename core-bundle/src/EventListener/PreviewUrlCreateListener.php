<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\ArticleModel;
use Contao\CoreBundle\DataContainer\DcaHierarchy;
use Contao\CoreBundle\DataContainer\DcaUrlAnalyzer;
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * @internal
 */
#[AsEventListener]
class PreviewUrlCreateListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly DcaUrlAnalyzer $dcaUrlAnalyzer,
        private readonly DcaHierarchy $dcaHierarchy,
    ) {
    }

    /**
     * Adds the page ID to the front end preview URL.
     */
    public function __invoke(PreviewUrlCreateEvent $event): void
    {
        if (!$this->framework->isInitialized() || !\in_array($event->getKey(), ['page', 'article'], true)) {
            return;
        }

        $pageId = $event->getId();

        if ('article' === $event->getKey()) {
            [$table, $id] = $this->dcaUrlAnalyzer->getCurrentTableId();

            // List view of articles
            if (null === $id) {
                return;
            }

            if ('tl_content' === $table) {
                [$table, $id] = $this->dcaHierarchy->getParentTableAndId($id, $table);
            }

            if ('tl_article' !== $table) {
                return;
            }

            $pageId = $this->framework->getAdapter(ArticleModel::class)->findById($id)?->pid;
        }

        if ($pageId) {
            $event->setQuery('page='.$pageId);
        }
    }
}
