<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Contao\CoreBundle\Fragment\ContentElement\ContentElementRendererInterface;
use Contao\CoreBundle\Fragment\FragmentRegistryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Proxy for new content element fragments so they are accessible via $GLOBALS['TL_CTE'].
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class ContentProxy extends ContentElement
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $container = \System::getContainer();
        $response = new Response();

        /** @var ContentElementRendererInterface $contentElementRenderer */
        $contentElementRenderer = $container->get(FragmentRegistryInterface::CONTENT_ELEMENT_RENDERER);

        $result = $contentElementRenderer->render($this->objModel, $this->strColumn);

        if (null !== $result) {
            $response->setContent($result);
        }

        return $response->getContent();
    }

    /**
     * {@inheritdoc}
     */
    protected function compile()
    {
        // noop
    }
}
