<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Autoload;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Finds the autoload bundles and adds them to the resolver.
 *
 * @author Leo Feyer <https://contao.org>
 */
class BundleAutoloader
{
    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @var string
     */
    protected $environment;

    /**
     * Constructor.
     *
     * @param string $rootDir     The kernel root directory
     * @param string $environment The current environment
     */
    public function __construct($rootDir, $environment)
    {
        $this->rootDir     = $rootDir;
        $this->environment = $environment;
    }

    /**
     * Returns an ordered bundle map for the current environment.
     *
     * @return array The bundles map
     */
    public function load()
    {
        $resolver = new ConfigResolver();

        $this->addBundlesToResolver($resolver, $this->findAutoloadFiles(), new JsonParser());
        $this->addBundlesToResolver($resolver, $this->findLegacyModules(), new IniParser());

        return $resolver->getBundlesMapForEnvironment($this->environment);
    }

    /**
     * Finds the autoload.json files of the bundles.
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
     * Finds the Contao legacy modules in system/module.
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
     * Adds one configuration object per bundle to the resolver.
     *
     * @param ConfigResolver  $resolver The resolver object
     * @param Finder          $files    The finder object
     * @param ParserInterface $parser   The parser object
     */
    protected function addBundlesToResolver(ConfigResolver $resolver, Finder $files, ParserInterface $parser)
    {
        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $configs = $parser->parse($file);

            foreach ($configs['bundles'] as $config) {
                $resolver->add($this->createConfigObject($config));
            }
        }
    }

    /**
     * Creates a configuration object and returns it.
     *
     * @param array $config The configuration array
     *
     * @return Config The configuration object
     */
    protected function createConfigObject(array $config)
    {
        return Config::create()
            ->setName($config['name'])
            ->setClass($config['class'])
            ->setReplace($config['replace'])
            ->setEnvironments($config['environments'])
            ->setLoadAfter($config['load-after'])
        ;
    }
}
