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
    private const SERVICES = [
        'assets.packages',
        'debug.stopwatch',
        'fragment.handler',
        'lexik_maintenance.driver.factory',
        'monolog.logger.contao',
        'security.authentication_utils',
        'security.authentication.trust_resolver',
        'security.firewall.map',
        'security.logout_url_generator',
        'security.helper',
        'uri_signer',
    ];

    private const ALIASES = [
        'database_connection',
        'mailer',
        'security.password_hasher_factory',
    ];

    public function process(ContainerBuilder $container): void
    {
        foreach (self::SERVICES as $service) {
            if (!$container->hasDefinition($service)) {
                continue;
            }

            $definition = $container->getDefinition($service);
            $definition->setPublic(true);
        }

        foreach (self::ALIASES as $alias) {
            if (!$container->hasAlias($alias)) {
                continue;
            }

            $alias = $container->getAlias($alias);
            $alias->setPublic(true);
        }
    }
}
