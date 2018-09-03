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
    private $projectDir;

    /**
     *  @param string $projectDir
     */
    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('contao_manager');

        $rootNode->children()
            ->scalarNode('manager_path')
                ->info('Path to the manager relative to the web dir.')
                ->defaultValue(null)
                ->validate()
                    ->always(
                        function (?string $path): ?string {
                            if (null === $path || is_file($this->projectDir . '/web/' . $path)) {
                                return $path;
                            }

                            throw new InvalidConfigurationException(
                                sprintf(
                                    'contao_manager.manager_path is configured but file "%s" does not exist.',
                                    $this->projectDir . '/web/' . $path
                                )
                            );
                        }
                    )
            ->end();

        return $treeBuilder;
    }
}
