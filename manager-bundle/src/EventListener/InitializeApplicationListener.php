<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\EventListener;

use Symfony\Component\Filesystem\Filesystem;

class InitializeApplicationListener
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * @param string $rootDir
     */
    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * Adds the initialize.php file.
     */
    public function onInitializeApplication(): void
    {
        $source = __DIR__.'/../Resources/system/initialize.php';
        $target = $this->rootDir.'/system/initialize.php';

        if (md5_file($source) === md5_file($target)) {
            return;
        }

        (new Filesystem())->copy($source, $target, true);
    }
}
