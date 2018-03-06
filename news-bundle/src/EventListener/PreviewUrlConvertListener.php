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

use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\News;
use Contao\NewsModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PreviewUrlConvertListener
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @param RequestStack             $requestStack The request stack
     * @param ContaoFrameworkInterface $framework    The Contao framework service
     */
    public function __construct(RequestStack $requestStack, ContaoFrameworkInterface $framework)
    {
        $this->requestStack = $requestStack;
        $this->framework = $framework;
    }

    /**
     * Modifies the front end preview URL.
     *
     * @param PreviewUrlConvertEvent $event The event object
     */
    public function onPreviewUrlConvert(PreviewUrlConvertEvent $event): void
    {
        if (!$this->framework->isInitialized()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || null === ($news = $this->getNewsModel($request))) {
            return;
        }

        /** @var News $newsAdapter */
        $newsAdapter = $this->framework->getAdapter(News::class);

        $event->setUrl($request->getSchemeAndHttpHost().'/'.$newsAdapter->generateNewsUrl($news));
    }

    /**
     * Returns the news model.
     *
     * @param Request $request The request object
     *
     * @return NewsModel|null The news model or null
     */
    private function getNewsModel(Request $request): ?NewsModel
    {
        if (!$request->query->has('news')) {
            return null;
        }

        /** @var NewsModel $adapter */
        $adapter = $this->framework->getAdapter(NewsModel::class);

        return $adapter->findByPk($request->query->get('news'));
    }
}
