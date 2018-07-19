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
 */
class MakeServicesPublicPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        static $services = [
            'assets.packages',
            'fragment.handler',
            'lexik_maintenance.driver.factory',
            'monolog.logger.contao',
            'security.authentication.trust_resolver',
            'security.firewall.map',
            'security.logout_url_generator',
        ];

        foreach ($services as $service) {
            $definition = $container->getDefinition($service);
            $definition->setPublic(true);
        }

        static $aliases = [
            'database_connection',
            'swiftmailer.mailer',
        ];

        foreach ($aliases as $alias) {
            $alias = $container->getAlias($alias);
            $alias->setPublic(true);
        }
    }
}
