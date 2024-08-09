<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\AccessDecisionStrategyPass;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Security\Core\Authorization\Strategy\AccessDecisionStrategyInterface;
use Symfony\Component\Security\Core\Authorization\Strategy\PriorityStrategy;

class AccessDecisionStrategyPassTest extends TestCase
{
    public function testDoesNothingWithoutAccessDecisionManager(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects($this->once())
            ->method('hasDefinition')
            ->with('security.access.decision_manager')
            ->willReturn(false)
        ;

        $container
            ->expects($this->never())
            ->method('getDefinition')
            ->with('security.access.decision_manager')
        ;

        $pass = new AccessDecisionStrategyPass();
        $pass->process($container);
    }

    public function testReplacesTheAccessDecisionStrategy(): void
    {
        $strategy = $this->createMock(AccessDecisionStrategyInterface::class);

        $definition = $this->createMock(Definition::class);
        $definition
            ->expects($this->once())
            ->method('getArgument')
            ->with(1)
            ->willReturn($strategy)
        ;

        $definition
            ->expects($this->once())
            ->method('replaceArgument')
            ->with(
                1,
                $this->callback(
                    static fn (Definition $definition) => $definition->getArgument(0) === $strategy
                        && $definition->getArgument(1) instanceof Definition
                        && PriorityStrategy::class === $definition->getArgument(1)->getClass()
                        && 'request_stack' === (string) $definition->getArgument(2)
                        && 'security.firewall.map' === (string) $definition->getArgument(3),
                ),
            )
        ;

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects($this->once())
            ->method('hasDefinition')
            ->with('security.access.decision_manager')
            ->willReturn(true)
        ;

        $container
            ->expects($this->once())
            ->method('getDefinition')
            ->with('security.access.decision_manager')
            ->willReturn($definition)
        ;

        $pass = new AccessDecisionStrategyPass();
        $pass->process($container);
    }
}
