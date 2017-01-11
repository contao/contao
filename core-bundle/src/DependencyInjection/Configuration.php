<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection;

use Imagine\Image\ImageInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Adds the Contao configuration structure.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @var bool
     */
    private $debug;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * Constructor.
     *
     * @param bool   $debug
     * @param string $rootDir
     */
    public function __construct($debug, $rootDir)
    {
        $this->debug = (bool) $debug;
        $this->rootDir = $rootDir;
    }

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('contao');

        $rootNode
            ->children()
                ->scalarNode('root_dir')
                    ->cannotBeEmpty()
                    ->defaultValue($this->resolveRootDir($this->rootDir.'/..'))
                    ->validate()
                        ->always(function ($value) {
                            return $this->resolveRootDir($value);
                        })
                    ->end()
                ->end()
                ->booleanNode('prepend_locale')
                    ->defaultFalse()
                ->end()
                ->scalarNode('encryption_key')
                    ->cannotBeEmpty()
                    ->defaultValue('%kernel.secret%')
                ->end()
                ->scalarNode('url_suffix')
                    ->defaultValue('.html')
                ->end()
                ->scalarNode('upload_path')
                    ->cannotBeEmpty()
                    ->defaultValue('files')
                    ->validate()
                        ->ifTrue(function ($v) {
                            return preg_match(
                                '@^(app|assets|contao|plugins|share|system|templates|vendor|web)(/|$)@',
                                $v
                            );
                        })
                        ->thenInvalid('%s')
                    ->end()
                ->end()
                ->scalarNode('csrf_token_name')
                    ->cannotBeEmpty()
                    ->defaultValue('contao_csrf_token')
                ->end()
                ->booleanNode('pretty_error_screens')
                    ->defaultValue(!$this->debug)
                ->end()
                ->integerNode('error_level')
                    ->min(-1)
                    ->max(32767)
                    ->defaultValue(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED)
                ->end()
                ->arrayNode('image')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('bypass_cache')
                            ->defaultValue($this->debug)
                        ->end()
                        ->scalarNode('target_path')
                            ->defaultValue('assets/images')
                        ->end()
                        ->arrayNode('valid_extensions')
                            ->prototype('scalar')->end()
                            ->defaultValue(['jpg', 'jpeg', 'gif', 'png', 'tif', 'tiff', 'bmp', 'svg', 'svgz'])
                        ->end()
                        ->arrayNode('imagine_options')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->integerNode('jpeg_quality')
                                    ->defaultValue(80)
                                ->end()
                                ->scalarNode('interlace')
                                    ->defaultValue(ImageInterface::INTERLACE_PLANE)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('security')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('disable_ip_check')
                            ->defaultFalse()
                        ->end()
                    ->end()
                ->end()
                ->variableNode('localconfig')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * Resolves the Contao root directory.
     *
     * @param string $value
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    private function resolveRootDir($value)
    {
        $path = realpath($value);

        if (false === $path) {
            throw new \InvalidArgumentException(sprintf('%s is not a resolvable path', $value));
        }

        return $path;
    }
}
