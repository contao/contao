<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
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
        $source = __DIR__.'/../Resources/system/initialize.php';
        $target = $this->rootDir.'/system/initialize.php';

        if (md5_file($source) === md5_file($target)) {
            return;
        }

        (new Filesystem())->copy($source, $target, true);
    }
}
