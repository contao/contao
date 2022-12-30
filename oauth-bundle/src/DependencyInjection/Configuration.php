<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthBundle\DependencyInjection;

use KnpU\OAuth2ClientBundle\DependencyInjection\KnpUOAuth2ClientExtension;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('contao_oauth');
        $treeBuilder
            ->getRootNode()
            ->children()
                ->arrayNode('enabled_providers')
                    ->info('Defines the available OAuth provider types that can be used for the OAuth clients in Contao.')
                    ->prototype('scalar')->end()
                    ->defaultValue(['amazon', 'facebook', 'github', 'google', 'slack'])
                    ->validate()
                    ->ifTrue(
                        static function (array $enabledTypes): bool {
                            $supportedTypes =  KnpUOAuth2ClientExtension::getAllSupportedTypes();

                            foreach ($enabledTypes as $enabledProvider) {
                                if (!\in_array($enabledProvider, $supportedTypes, true)) {
                                    return true;
                                }
                            }

                            return false;
                        }
                    )
                    ->thenInvalid('Unknown providers supplied in the list of enabled OAuth providers (%s).')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
