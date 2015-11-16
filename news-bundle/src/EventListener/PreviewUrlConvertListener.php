<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\EventListener;

use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\News;
use Contao\NewsModel;
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

        if (!$request->query->has('news')) {
            return;
        }

        /** @var NewsModel $newsModel */
        $newsModel = $this->framework->getAdapter('Contao\NewsModel');

        if (null === ($news = $newsModel->findByPk($request->query->get('news')))) {
            return;
        }

        /** @var News $newsAdapter */
        $newsAdapter = $this->framework->getAdapter('Contao\News');

        $event->setUrl($request->getSchemeAndHttpHost() . '/' . $newsAdapter->generateNewsUrl($news));
    }
}
