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

use Contao\CoreBundle\Swiftmailer\AvailableMailers;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AvailableMailersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $swiftmailerConfig = $container->getExtensionConfig('swiftmailer');

        $definition = $container->findDefinition(AvailableMailers::class);

        $mailers = [];

        foreach ($swiftmailerConfig as $v) {
            if (isset($v['mailers'])) {
                foreach (array_keys($v['mailers']) as $mailerName) {
                    $swiftmailerServiceId = 'swiftmailer.mailer.'.$mailerName;

                    if ($container->hasDefinition($swiftmailerServiceId)) {
                        $mailers[$mailerName] = new Reference($swiftmailerServiceId);
                    }
                }
            }
        }

        $definition->addMethodCall('setMailers', [$mailers]);
    }
}
