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

/**
 * @internal
 */
class RemembermeServicesPass implements CompilerPassInterface
{
    public const OVERRIDE_PREFIX = 'security.authentication.rememberme.services.simplehash';
    public const TEMPLATE_ID = 'contao.security.expiring_token_based_remember_me_services';

    private string $firewallName;

    public function __construct(string $firewallName)
    {
        $this->firewallName = $firewallName;
    }

    public function process(ContainerBuilder $container): void
    {
        $overrideId = self::OVERRIDE_PREFIX.'.'.$this->firewallName;

        if (!$container->hasDefinition($overrideId)) {
            return;
        }

        $serviceId = self::TEMPLATE_ID.'.'.$this->firewallName;
        $override = $container->getDefinition($overrideId);

        $definition = $container->setDefinition($serviceId, new ChildDefinition(self::TEMPLATE_ID));
        $definition->replaceArgument(4, $override->getArgument(3));
        $definition->replaceArgument(3, $override->getArgument(2));
        $definition->replaceArgument(2, $override->getArgument(1));
        $definition->replaceArgument(1, $override->getArgument(0));

        $container->setAlias($overrideId, $serviceId);
    }
}
