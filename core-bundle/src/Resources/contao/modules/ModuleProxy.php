<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Contao\CoreBundle\Fragment\FragmentRegistryInterface;
use Contao\CoreBundle\Fragment\FrontendModule\FrontendModuleRendererInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Proxy for new front end module fragments so they are accessible via $GLOBALS['FE_MOD'].
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class ModuleProxy extends Module
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $container = \System::getContainer();
        $response = new Response();

        /** @var FrontendModuleRendererInterface $frontendModuleRenderer */
        $frontendModuleRenderer = $container->get(FragmentRegistryInterface::FRONTEND_MODULE_RENDERER);

        $result = $frontendModuleRenderer->render($this->objModel, $this->strColumn);

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
