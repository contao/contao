<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\HttpKernel\Bundle;

use Contao\CoreBundle\Analyzer\HtaccessAnalyzer;
use Contao\CoreBundle\DependencyInjection\Compiler\AddContaoResourcesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Converts a Contao module in system/modules into a bundle.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
final class ContaoModuleBundle extends Bundle
{
    /**
     * Sets the module name and application root directory.
     *
     * @param string $name    The module name
     * @param string $rootDir The application root directory
     */
    public function __construct($name, $rootDir)
    {
        $this->name = $name;
        $this->path = dirname($rootDir) . '/system/modules/' . $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(
            new AddContaoResourcesPass($this->getPath(), $this->findPublicFolders())
        );
    }

    /**
     * Finds the public folders.
     *
     * @return array The public folders
     */
    private function findPublicFolders()
    {
        $dirs  = [];
        $files = $this->findHtaccessFiles();

        /** @var SplFileInfo[] $files */
        foreach ($files as $file) {
            $htaccess = new HtaccessAnalyzer($file);

            if ($htaccess->grantsAccess()) {
                $dirs[] = $file->getPath();
            }
        }

        return $dirs;
    }

    /**
     * Finds the .htaccess files in the Contao directory.
     *
     * @return Finder The finder object
     */
    private function findHtaccessFiles()
    {
        return Finder::create()
            ->files()
            ->name('.htaccess')
            ->ignoreDotFiles(false)
            ->in($this->getPath())
        ;
    }
}
