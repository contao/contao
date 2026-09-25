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

use Contao\CoreBundle\Security\Authentication\ContaoStrategy;
use Contao\CoreBundle\Security\Authentication\ContaoStrategyContext;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Core\Authorization\Strategy\PriorityStrategy;

/**
 * @internal
 */
class AccessDecisionStrategyPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('security.access.decision_manager')) {
            return;
        }

        $accessDecisionManager = $container->getDefinition('security.access.decision_manager');
        $originalStrategy = $accessDecisionManager->getArgument(1);

        $strategyContext = new Definition(ContaoStrategyContext::class, [
            new Reference('request_stack'),
            new Reference('security.firewall.map'),
        ]);
        $container->setDefinition('contao.security.authentication.contao_strategy_context', $strategyContext);

        $strategy = new Definition(ContaoStrategy::class, [
            $originalStrategy,
            new Definition(PriorityStrategy::class),
            new Reference('contao.security.authentication.contao_strategy_context'),
        ]);
        $container->setDefinition('contao.security.authentication.contao_strategy', $strategy);

        $accessDecisionManager->replaceArgument(1, new Reference('contao.security.authentication.contao_strategy'));
    }
}
