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
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
class PreviewUrlCreateListener
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(RequestStack $requestStack, ContaoFramework $framework)
    {
        $this->requestStack = $requestStack;
        $this->framework = $framework;
    }

    /**
     * Adds the page ID to the front end preview URL.
     *
     * @throws \RuntimeException
     */
    public function __invoke(PreviewUrlCreateEvent $event): void
    {
        if (!$this->framework->isInitialized() || (!$id = $event->getId()) || !\in_array($event->getKey(), ['page', 'article'], true)) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new \RuntimeException('The request stack did not contain a request');
        }

        if ('article' === $event->getKey()) {
            /** @var ArticleModel $adapter */
            $adapter = $this->framework->getAdapter(ArticleModel::class);

            if (!$article = $adapter->findByPk($id)) {
                return;
            }

            $id = $article->pid;
        }

        /** @var PageModel $adapter */
        $adapter = $this->framework->getAdapter(PageModel::class);

        if (null !== $adapter->findByPk($id)) {
            $event->setQuery('page='.$id);
        }
    }
}
