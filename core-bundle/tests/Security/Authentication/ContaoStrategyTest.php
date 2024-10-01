<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Authentication;

use Contao\CoreBundle\Security\Authentication\ContaoStrategy;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\Strategy\AccessDecisionStrategyInterface;
use Symfony\Component\Security\Http\FirewallMapInterface;

class ContaoStrategyTest extends TestCase
{
    public function testUsesDefaultDecisionStrageyOnWrongFirewallMap(): void
    {
        $accessDecisionManager = new ContaoStrategy(
            $this->mockAccessDecisionStrategy(true),
            $this->mockAccessDecisionStrategy(false),
            new RequestStack(),
            $this->createMock(FirewallMapInterface::class),
        );

        $accessDecisionManager->decide($this->createMock(\Traversable::class));
    }

    public function testUsesDefaultDecisionStrategyIfNoRequestAvailable(): void
    {
        $accessDecisionManager = new ContaoStrategy(
            $this->mockAccessDecisionStrategy(true),
            $this->mockAccessDecisionStrategy(false),
            new RequestStack(),
            $this->mockFirewallMap(null),
        );

        $accessDecisionManager->decide($this->createMock(\Traversable::class));
    }

    public function testUsesDefaultDecisionStrategyIfFirewallMapHasNoConfig(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $accessDecisionManager = new ContaoStrategy(
            $this->mockAccessDecisionStrategy(true),
            $this->mockAccessDecisionStrategy(false),
            $requestStack,
            $this->mockFirewallMap(null),
        );

        $accessDecisionManager->decide($this->createMock(\Traversable::class));
    }

    public function testUsesPriorityStrategyIfContaoFrontendRequest(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [], ['_scope' => 'frontend']));

        $accessDecisionManager = new ContaoStrategy(
            $this->mockAccessDecisionStrategy(false),
            $this->mockAccessDecisionStrategy(true),
            $requestStack,
            $this->mockFirewallMap('contao_frontend'),
        );

        $accessDecisionManager->decide($this->createMock(\Traversable::class));
    }

    public function testUsesPriorityStrategyIfContaoBackendRequest(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [], ['_scope' => 'backend']));

        $accessDecisionManager = new ContaoStrategy(
            $this->mockAccessDecisionStrategy(false),
            $this->mockAccessDecisionStrategy(true),
            $requestStack,
            $this->mockFirewallMap('contao_backend'),
        );

        $accessDecisionManager->decide($this->createMock(\Traversable::class));
    }

    private function mockAccessDecisionStrategy(bool $shouldBeCalled): AccessDecisionStrategyInterface&MockObject
    {
        $manager = $this->createMock(AccessDecisionStrategyInterface::class);
        $manager
            ->expects($shouldBeCalled ? $this->once() : $this->never())
            ->method('decide')
            ->willReturn(true)
        ;

        return $manager;
    }

    private function mockFirewallMap(string|null $context): FirewallMap&MockObject
    {
        $map = $this->createMock(FirewallMap::class);

        if (null === $context) {
            $map
                ->method('getFirewallConfig')
                ->willReturn(null)
            ;
        } else {
            $map
                ->expects($this->once())
                ->method('getFirewallConfig')
                ->willReturn(new FirewallConfig($context, '', null, true, false, null, $context))
            ;
        }

        return $map;
    }
}
