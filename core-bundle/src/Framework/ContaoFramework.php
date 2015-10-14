<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Framework;

use Contao\Config;
use Contao\CoreBundle\Adapter\ConfigAdapter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Initializes the Contao framework.
 *
 * Currently an alias to the original framework class
 * which will be renamed in Contao 5.
 */
class ContaoFramework extends \Contao\CoreBundle\ContaoFramework
{
    /**
     * @var Adapter|Config
     */
    private $config;

    public function __construct(
        RequestStack $requestStack,
        RouterInterface $router,
        SessionInterface $session,
        $rootDir,
        CsrfTokenManagerInterface $tokenManager,
        $csrfTokenName,
        ConfigAdapter $config,
        $errorLevel
    ) {
        parent::__construct(
            $requestStack,
            $router,
            $session,
            $rootDir,
            $tokenManager,
            $csrfTokenName,
            $config,
            $errorLevel
        );

        $this->config = $this->getAdapter('Config');
    }

    protected function configPreload()
    {
        $this->config->preload();
    }

    protected function configInitialize()
    {
        $this->config->getInstance();
    }

    protected function configIsComplete()
    {
        return $this->config->getInstance()->isComplete();
    }

    protected function configGet($key)
    {
        return $this->config->get($key);
    }
}
