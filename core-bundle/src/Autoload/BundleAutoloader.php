<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\CoreBundle\Autoload;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Finds the autoload bundles
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
     * Constructor
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
     * Returns an ordered bundle map
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
        $factory = new ConfigFactory();

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $configs = $parser->parse($file);

            foreach ($configs['bundles'] as $config) {
                $resolver->add($factory->create($config));
            }
        }
    }
}
