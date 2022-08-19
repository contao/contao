<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Makes services public that we need to retrieve directly.
 *
 * @internal
 */
class MakeServicesPublicPass implements CompilerPassInterface
{
    private const IDS = [
        'assets.packages',
        'database_connection',
        'debug.stopwatch',
        'filesystem',
        'fragment.handler',
        'mailer',
        'monolog.logger.contao',
        'security.authentication_utils',
        'security.authentication.trust_resolver',
        'security.authorization_checker',
        'security.encoder_factory',
        'security.firewall.map',
        'security.helper',
        'security.logout_url_generator',
        'security.password_hasher_factory',
        'security.token_storage',
        'twig',
        'uri_signer',
    ];

    public function process(ContainerBuilder $container): void
    {
        foreach (self::IDS as $id) {
            if ($container->hasAlias($id)) {
                $alias = $container->getAlias($id);
                $alias->setPublic(true);
            } elseif ($container->hasDefinition($id)) {
                $definition = $container->getDefinition($id);
                $definition->setPublic(true);
            }
        }
    }
}
