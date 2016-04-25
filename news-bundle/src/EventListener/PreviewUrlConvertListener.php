<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\EventListener;

use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\News;
use Contao\NewsModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Converts the front end preview URL.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
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
     * Constructor.
     *
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
    public function onPreviewUrlConvert(PreviewUrlConvertEvent $event)
    {
        if (!$this->framework->isInitialized()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || null === ($news = $this->getNewsModel($request))) {
            return;
        }

        /** @var News $newsAdapter */
        $newsAdapter = $this->framework->getAdapter('Contao\News');

        $event->setUrl($request->getSchemeAndHttpHost().'/'.$newsAdapter->generateNewsUrl($news));
    }

    /**
     * Returns the news model.
     *
     * @param Request $request The request object
     *
     * @return NewsModel|null The news model or null
     */
    private function getNewsModel(Request $request)
    {
        if (!$request->query->has('news')) {
            return null;
        }

        /** @var NewsModel $adapter */
        $adapter = $this->framework->getAdapter('Contao\NewsModel');

        return $adapter->findByPk($request->query->get('news'));
    }
}
