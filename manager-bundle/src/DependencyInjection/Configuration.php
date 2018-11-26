<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

final class Configuration implements ConfigurationInterface
{
    /**
     * @var string
     */
    private $webDir;

    public function __construct(string $webDir)
    {
        $this->webDir = $webDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('contao_manager');
        $rootNode
            ->children()
                ->scalarNode('manager_path')
                    ->defaultValue(null)
                    ->validate()
                        ->always(
                            function (?string $path): ?string {
                                if (null === $path || is_file($this->webDir.'/'.$path)) {
                                    return $path;
                                }

                                throw new InvalidConfigurationException(
                                    sprintf(
                                        'contao_manager.manager_path is configured but file "%s" does not exist.',
                                        $this->webDir.'/'.$path
                                    )
                                );
                            }
                        )
                    ->end()
                    ->info('Path to the manager relative to the web dir.')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
