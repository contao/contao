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
    private $projectDir;

    /**
     * @param string $projectDir
     */
    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * Adds the initialize.php file.
     */
    public function onInitializeApplication(): void
    {
        $source = __DIR__.'/../Resources/system/initialize.php';
        $target = $this->projectDir.'/system/initialize.php';

        if (md5_file($source) === md5_file($target)) {
            return;
        }

        (new Filesystem())->copy($source, $target, true);
    }
}
