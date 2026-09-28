<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\EventListener;

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
     * Adds the news ID to the front end preview URL.
     */
    public function __invoke(PreviewUrlCreateEvent $event): void
    {
        if (!$this->framework->isInitialized() || 'news' !== $event->getKey()) {
            return;
        }

        [$table, $id] = $this->dcaUrlAnalyzer->getCurrentTableId();

        // Return on the news archive list page
        if (null === $id || !\in_array($table, ['tl_news', 'tl_content'], true)) {
            return;
        }

        if ('tl_content' === $table) {
            [$table, $id] = $this->dcaHierarchy->getParentTableAndId($id, $table);
        }

        if ('tl_news' !== $table) {
            return;
        }

        $event->setQuery('news='.$id);
    }
}
