<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Verifies and optimizes the paths in the contao.resoures service.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class OptimizeContaoResourcesPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * Constructor.
     *
     * @param string $rootDir The kernel root directory
     */
    public function __construct($rootDir)
    {
        $this->rootDir = dirname($rootDir) . '/';
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('contao.resource_provider')) {
            return;
        }

        $resourcesPaths = [];
        $publicFolders  = [];
        $definition     = $container->getDefinition('contao.resource_provider');
        $calls          = $definition->getMethodCalls();

        foreach ($calls as $k => $call) {
            if ('addResourcesPath' === $call[0]) {
                $resourcesPaths[] = $this->validatePath($call[1][0]);
                unset($calls[$k]);
            } elseif ('addPublicFolders' === $call[0]) {
                $this->mergePaths($publicFolders, $call[1][0]);
                unset($calls[$k]);
            }
        }

        $definition->setMethodCalls($calls);
        $definition->setArguments([$resourcesPaths, $publicFolders]);
    }

    /**
     * Adds relative paths to an array making sure they actually exist.
     *
     * @param array $current Current paths
     * @param array $new     Paths to be added
     */
    private function mergePaths(array &$current, array $new)
    {
        foreach ($new as $path) {
            $path = $this->validatePath($path);
            $path = str_replace($this->rootDir, '', $path);

            $current[] = $path;
        }
    }

    /**
     * Ensures that the given path exists.
     *
     * @param string $path The path
     *
     * @return string The path
     *
     * @throws \InvalidArgumentException If the path does not exist
     */
    private function validatePath($path)
    {
        if (false !== strpos($path, '../')) {
            $path = realpath($path);
        }

        if (!is_dir($path)) {
            throw new \InvalidArgumentException("Path $path does not exist");
        }

        return $path;
    }
}
