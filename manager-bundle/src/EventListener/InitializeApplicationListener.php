<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\EventListener;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Listens to the contao_installation.initialize_application event.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InitializeApplicationListener
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * Constructor.
     *
     * @param string $rootDir
     */
    public function __construct($rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * Adds the initialize.php file.
     */
    public function onInitializeApplication()
    {
        (new Filesystem())
            ->copy(__DIR__.'/../Resources/system/initialize.php', $this->rootDir.'/system/initialize.php', true)
        ;
    }
}
