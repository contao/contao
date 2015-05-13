<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Framework\FrameworkInitializer;

/**
 * Initializes the Contao framework.
 *
 * @author Dominik Tomasi <https://github.com/dtomasi>
 */
class InitializeSystemListener extends AbstractScopeAwareListener
{
    /**
     * @var FrameworkInitializer
     */
    private $initializer;

    /**
     * Constructor.
     *
     * @param FrameworkInitializer $initializer The Initializer
     */
    public function __construct(FrameworkInitializer $initializer)
    {
        $this->initializer = $initializer;
    }

    /**
     * Initializes the system upon kernel.request.
     */
    public function onKernelRequest()
    {
        if (!$this->isContaoScope()) {
            return;
        }

        $this->initializer->initialize();
    }

    /**
     * Initializes the system upon console.command.
     */
    public function onConsoleCommand()
    {
        $this->initializer->initialize();
    }
}
