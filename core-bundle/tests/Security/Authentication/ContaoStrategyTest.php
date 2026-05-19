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
use Contao\CoreBundle\Security\Authentication\ContaoStrategyContext;
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
            new ContaoStrategyContext(new RequestStack(), $this->createStub(FirewallMapInterface::class)),
        );

        $accessDecisionManager->decide($this->createStub(\Traversable::class));
    }

    public function testUsesDefaultDecisionStrategyIfNoRequestAvailable(): void
    {
        $accessDecisionManager = new ContaoStrategy(
            $this->mockAccessDecisionStrategy(true),
            $this->mockAccessDecisionStrategy(false),
            new ContaoStrategyContext(new RequestStack(), $this->createStub(FirewallMap::class)),
        );

        $accessDecisionManager->decide($this->createStub(\Traversable::class));
    }

    public function testUsesDefaultDecisionStrategyIfFirewallMapHasNoConfig(): void
    {
        $requestStack = new RequestStack([new Request()]);

        $accessDecisionManager = new ContaoStrategy(
            $this->mockAccessDecisionStrategy(true),
            $this->mockAccessDecisionStrategy(false),
            new ContaoStrategyContext($requestStack, $this->createStub(FirewallMap::class)),
        );

        $accessDecisionManager->decide($this->createStub(\Traversable::class));
    }

    public function testUsesPriorityStrategyIfContaoFrontendRequest(): void
    {
        $requestStack = new RequestStack([new Request([], [], ['_scope' => 'frontend'])]);

        $accessDecisionManager = new ContaoStrategy(
            $this->mockAccessDecisionStrategy(false),
            $this->mockAccessDecisionStrategy(true),
            new ContaoStrategyContext($requestStack, $this->mockFirewallMap('contao_frontend')),
        );

        $accessDecisionManager->decide($this->createStub(\Traversable::class));
    }

    public function testUsesPriorityStrategyIfContaoBackendRequest(): void
    {
        $requestStack = new RequestStack([new Request([], [], ['_scope' => 'backend'])]);

        $accessDecisionManager = new ContaoStrategy(
            $this->mockAccessDecisionStrategy(false),
            $this->mockAccessDecisionStrategy(true),
            new ContaoStrategyContext($requestStack, $this->mockFirewallMap('contao_backend')),
        );

        $accessDecisionManager->decide($this->createStub(\Traversable::class));
    }

    public function testCanForceTheContaoStrategyWithoutARequest(): void
    {
        $strategyContext = new ContaoStrategyContext(new RequestStack(), $this->createStub(FirewallMap::class));
        $accessDecisionManager = new ContaoStrategy(
            $this->mockAccessDecisionStrategy(false),
            $this->mockAccessDecisionStrategy(true),
            $strategyContext,
        );

        $strategyContext->runInContext(
            ContaoStrategyContext::CONTEXT_BACKEND,
            fn () => $accessDecisionManager->decide($this->createStub(\Traversable::class)),
        );
    }

    public function testRestoresTheContextIfTheCallbackThrows(): void
    {
        $strategyContext = new ContaoStrategyContext(new RequestStack(), $this->createStub(FirewallMap::class));

        try {
            $strategyContext->runInContext(
                ContaoStrategyContext::CONTEXT_BACKEND,
                static function (): void {
                    throw new \RuntimeException();
                },
            );
        } catch (\RuntimeException) {
            // Expected
        }

        $this->assertFalse($strategyContext->isContaoContext());
        $this->assertNull($strategyContext->getContext());
    }

    public function testCanNestExplicitContexts(): void
    {
        $strategyContext = new ContaoStrategyContext(new RequestStack(), $this->createStub(FirewallMap::class));

        $contexts = $strategyContext->runInContext(
            ContaoStrategyContext::CONTEXT_BACKEND,
            static fn (): array => [
                $strategyContext->getContext(),
                $strategyContext->runInContext(
                    ContaoStrategyContext::CONTEXT_FRONTEND,
                    static fn (): string|null => $strategyContext->getContext(),
                ),
                $strategyContext->getContext(),
            ],
        );

        $this->assertSame(['contao_backend', 'contao_frontend', 'contao_backend'], $contexts);
        $this->assertNull($strategyContext->getContext());
    }

    public function testRejectsInvalidExplicitContext(): void
    {
        $strategyContext = new ContaoStrategyContext(new RequestStack(), $this->createStub(FirewallMap::class));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Contao strategy context "invalid".');

        $strategyContext->runInContext('invalid', static fn (): null => null);
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

    private function mockFirewallMap(string $context): FirewallMap&MockObject
    {
        $map = $this->createMock(FirewallMap::class);
        $map
            ->expects($this->once())
            ->method('getFirewallConfig')
            ->willReturn(new FirewallConfig($context, '', null, true, false, null, $context))
        ;

        return $map;
    }
}
