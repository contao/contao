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

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RemembermeServicesPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $firewallName;

    public function __construct(string $firewallName)
    {
        $this->firewallName = $firewallName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $overrideId = 'security.authentication.rememberme.services.simplehash.'.$this->firewallName;

        if (!$container->hasDefinition($overrideId)) {
            return;
        }

        $templateId = 'contao.security.expiring_token_based_rememberme_services';
        $serviceId = $templateId.'.'.$this->firewallName;

        $override = $container->getDefinition($overrideId);
        $definition = new ChildDefinition($templateId);
        $definition->setArgument(1, $override->getArgument(0));
        $definition->setArgument(2, $override->getArgument(1));
        $definition->setArgument(3, $override->getArgument(2));
        $definition->setArgument(4, $override->getArgument(3));

        $container->setDefinition($serviceId, $definition);
        $container->setAlias($overrideId, $templateId.'.'.$this->firewallName);
    }
}
