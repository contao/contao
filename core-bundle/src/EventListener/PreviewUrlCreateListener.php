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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
class PreviewUrlCreateListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ContaoFramework $framework,
    ) {
    }

    /**
     * Adds the page ID to the front end preview URL.
     */
    public function __invoke(PreviewUrlCreateEvent $event): void
    {
        if (!$this->framework->isInitialized() || (!$id = $event->getId()) || !\in_array($event->getKey(), ['page', 'article'], true)) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request) {
            throw new \RuntimeException('The request stack did not contain a request');
        }

        if ('article' === $event->getKey()) {
            $adapter = $this->framework->getAdapter(ArticleModel::class);

            if (!$article = $adapter->findByPk($id)) {
                return;
            }

            $id = $article->pid;
        }

        $adapter = $this->framework->getAdapter(PageModel::class);

        if ($adapter->findByPk($id) instanceof PageModel) {
            $event->setQuery('page='.$id);
        }
    }
}
