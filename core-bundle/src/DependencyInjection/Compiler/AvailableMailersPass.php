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
use Contao\CoreBundle\Swiftmailer\MailerConfig;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AvailableMailersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $contaoConfig = array_merge(...$container->getExtensionConfig('contao'));
        $fromAddresses = [];

        if (isset($contaoConfig['mailers'], $contaoConfig['mailers']['from_addresses'])) {
            $fromAddresses = $contaoConfig['mailers']['from_addresses'];
        }

        $swiftmailerConfig = $container->getExtensionConfig('swiftmailer');
        $availableMailersDefinition = $container->findDefinition(AvailableMailers::class);

        foreach ($swiftmailerConfig as $v) {
            if (isset($v['mailers'])) {
                foreach (array_keys($v['mailers']) as $mailerName) {
                    $mailerServiceId = 'swiftmailer.mailer.'.$mailerName;

                    if ($container->hasDefinition($mailerServiceId)) {

                        $availableMailersDefinition->addMethodCall(
                            'addMailer',
                            [
                                new Definition(MailerConfig::class, 
                                    [
                                        $mailerName, 
                                        new Reference($mailerServiceId), 
                                        $fromAddresses[$mailerName] ?? null,
                                    ]
                                ),
                            ]
                        );
                    }
                }
            }
        }
    }
}
