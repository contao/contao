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

use Contao\CoreBundle\Mailer\AvailableTransports;
use Contao\CoreBundle\Mailer\TransportConfig;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class AddAvailableTransportsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(AvailableTransports::class)) {
            return;
        }

        $contaoConfig = array_merge(...$container->getExtensionConfig('contao'));
        $fromAddresses = [];

        if (isset($contaoConfig['mailer'], $contaoConfig['mailer']['from_addresses'])) {
            $fromAddresses = $contaoConfig['mailer']['from_addresses'];
        }

        $frameworkConfig = $container->getExtensionConfig('framework');
        $definition = $container->findDefinition(AvailableTransports::class);

        foreach ($frameworkConfig as $v) {
            if (isset($v['mailer'], $v['mailer']['transports'])) {
                foreach (array_keys($v['mailer']['transports']) as $transportName) {
                    $from = $fromAddresses[$transportName] ?? null;
                    $definition->addMethodCall(
                        'addTransport',
                        [
                            new Definition(TransportConfig::class, [$transportName, $from]),
                        ]
                    );
                }
            }
        }
    }
}
