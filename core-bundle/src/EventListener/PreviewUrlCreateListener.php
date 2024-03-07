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
use Contao\ContentModel;
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
        if (!$this->framework->isInitialized()) {
            return;
        }

        $id = $event->getId();

        if (!$id || !\in_array($event->getKey(), ['page', 'article', 'content'], true)) {
            return;
        }

        switch ($event->getKey()) {
            case 'content':
                $adapter = $this->framework->getAdapter(ContentModel::class);

                do {
                    if (!$element = $adapter->findByPk($id)) {
                        break;
                    }

                    $id = $element->pid;
                } while ('tl_content' === $element->ptable);
                // no break

            case 'article':
                $adapter = $this->framework->getAdapter(ArticleModel::class);

                if (!$article = $adapter->findByPk($id)) {
                    return;
                }

                $id = $article->pid;
                // no break

            default:
                $adapter = $this->framework->getAdapter(PageModel::class);

                if (!$adapter->findByPk($id)) {
                    return;
                }

                $event->setQuery('page='.$id);
        }
    }
}
