<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Autoload;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Finds the autoload bundles
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class BundleAutoloader
{
    /**
     * @var string
     */
    protected $rootDir;

    /**
     * Constructor
     *
     * @param string $rootDir The kernel root directory
     */
    public function __construct($rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * Returns an ordered bundle map
     *
     * @param string $environment
     *
     * @return array The bundles map
     */
    public function load($environment)
    {
        $resolver = new ConfigResolver();

        $this->addBundlesToResolver($resolver, $this->findAutoloadFiles(), new JsonParser());
        $this->addBundlesToResolver($resolver, $this->findLegacyModules(), new IniParser());

        return $resolver->getBundlesMapForEnvironment($environment);
    }

    /**
     * Finds the autoload.json files
     *
     * @return Finder The finder object
     */
    protected function findAutoloadFiles()
    {
        return Finder::create()
            ->files()
            ->name('autoload.json')
            ->notPath('tests/')
            ->notPath('Tests/')
            ->in(dirname($this->rootDir) . '/vendor')
        ;
    }

    /**
     * Finds the Contao legacy modules
     *
     * @return Finder The finder object
     */
    protected function findLegacyModules()
    {
        return Finder::create()
            ->directories()
            ->depth('== 0')
            ->ignoreDotFiles(true)
            ->sortByName()
            ->in(dirname($this->rootDir) . '/system/modules')
        ;
    }

    /**
     * Adds bundles to the resolver
     *
     * @param ConfigResolver  $resolver The resolver object
     * @param Finder          $files    The finder object
     * @param ParserInterface $parser   The parser object
     */
    protected function addBundlesToResolver(ConfigResolver $resolver, Finder $files, ParserInterface $parser)
    {
        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            foreach ($parser->parse($file) as $config) {
                $resolver->add($config);
            }
        }
    }
}
