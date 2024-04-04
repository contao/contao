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
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * @internal
 */
#[AsEventListener]
class PreviewUrlCreateListener
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    /**
     * Adds the page ID to the front end preview URL.
     */
    public function __invoke(PreviewUrlCreateEvent $event): void
    {
        if (!$this->framework->isInitialized() || (!$id = $event->getId()) || !\in_array($event->getKey(), ['page', 'article'], true)) {
            return;
        }

        if ('article' === $event->getKey()) {
            $adapter = $this->framework->getAdapter(ArticleModel::class);

            if (!$article = $adapter->findById($id)) {
                return;
            }

            $id = $article->pid;
        }

        $adapter = $this->framework->getAdapter(PageModel::class);

        if ($adapter->findById($id)) {
            $event->setQuery('page='.$id);
        }
    }
}
