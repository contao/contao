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
use Contao\CoreBundle\Security\Authentication\ContaoStrategy;
use Contao\CoreBundle\Security\Authentication\ContaoStrategyContext;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
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
        $strategy = $this->createStub(AccessDecisionStrategyInterface::class);
        $container = new ContainerBuilder();
        $container->setDefinition('security.access.decision_manager', new Definition(null, [null, $strategy]));

        $pass = new AccessDecisionStrategyPass();
        $pass->process($container);

        $accessDecisionManager = $container->getDefinition('security.access.decision_manager');
        $this->assertSame(
            'contao.security.authentication.contao_strategy',
            (string) $accessDecisionManager->getArgument(1),
        );

        $context = $container->getDefinition('contao.security.authentication.contao_strategy_context');
        $this->assertSame(ContaoStrategyContext::class, $context->getClass());
        $this->assertSame('request_stack', (string) $context->getArgument(0));
        $this->assertSame('security.firewall.map', (string) $context->getArgument(1));

        $contaoStrategy = $container->getDefinition('contao.security.authentication.contao_strategy');
        $this->assertSame(ContaoStrategy::class, $contaoStrategy->getClass());
        $this->assertSame($strategy, $contaoStrategy->getArgument(0));
        $this->assertInstanceOf(Definition::class, $contaoStrategy->getArgument(1));
        $this->assertSame(PriorityStrategy::class, $contaoStrategy->getArgument(1)->getClass());
        $this->assertInstanceOf(Reference::class, $contaoStrategy->getArgument(2));
    }
}
