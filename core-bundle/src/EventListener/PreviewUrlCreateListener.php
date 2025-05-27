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

use Contao\CoreBundle\DataContainer\DcaUrlAnalyzer;
use Contao\CoreBundle\DataContainer\DynamicPtableTrait;
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * @internal
 */
#[AsEventListener]
class PreviewUrlCreateListener
{
    use DynamicPtableTrait;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly DcaUrlAnalyzer $dcaUrlAnalyzer,
        private readonly Connection $connection,
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
                [$table, $id] = $this->getParentTableAndId($this->connection, $table, $id);
            }

            if ('tl_article' !== $table) {
                return;
            }

            $pageId = $this->connection->fetchOne('SELECT pid FROM tl_article WHERE id=?', [$id]);
        }

        $event->setQuery('page='.$pageId);
    }
}
