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

use Contao\CoreBundle\Security\Authentication\AccessDecisionManager;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Http\FirewallMapInterface;

class AccessDecisionManagerTest extends TestCase
{
    public function testUsesOriginalDecisionManagerOnWrongFirewallMap(): void
    {
        $accessDecisionManager = new AccessDecisionManager(
            $this->mockAccessDecisionManager(true),
            $this->mockAccessDecisionManager(false),
            new RequestStack(),
            $this->createMock(FirewallMapInterface::class),
        );

        $accessDecisionManager->decide($this->createMock(TokenInterface::class), []);
    }

    public function testUsesOriginalDecisionManagerIfNoRequestAvailable(): void
    {
        $accessDecisionManager = new AccessDecisionManager(
            $this->mockAccessDecisionManager(true),
            $this->mockAccessDecisionManager(false),
            new RequestStack(),
            $this->mockFirewallMap(null),
        );

        $accessDecisionManager->decide($this->createMock(TokenInterface::class), []);
    }

    public function testUsesOriginalDecisionManagerIfFirewallMapHasNoConfig(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $accessDecisionManager = new AccessDecisionManager(
            $this->mockAccessDecisionManager(true),
            $this->mockAccessDecisionManager(false),
            $requestStack,
            $this->mockFirewallMap(null),
        );

        $accessDecisionManager->decide($this->createMock(TokenInterface::class), []);
    }

    public function testUsesContaoDecisionManagerIfContaoFrontendRequest(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [], ['_scope' => 'frontend']));

        $accessDecisionManager = new AccessDecisionManager(
            $this->mockAccessDecisionManager(false),
            $this->mockAccessDecisionManager(true),
            $requestStack,
            $this->mockFirewallMap('contao_frontend'),
        );

        $accessDecisionManager->decide($this->createMock(TokenInterface::class), []);
    }

    public function testUsesContaoDecisionManagerIfContaoBackendRequest(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [], ['_scope' => 'frontend']));

        $accessDecisionManager = new AccessDecisionManager(
            $this->mockAccessDecisionManager(false),
            $this->mockAccessDecisionManager(true),
            $requestStack,
            $this->mockFirewallMap('contao_backend'),
        );

        $accessDecisionManager->decide($this->createMock(TokenInterface::class), []);
    }

    /**
     * @return AccessDecisionManagerInterface&MockObject
     */
    private function mockAccessDecisionManager(bool $shouldBeCalled): AccessDecisionManagerInterface
    {
        $manager = $this->createMock(AccessDecisionManagerInterface::class);
        $manager
            ->expects($shouldBeCalled ? $this->once() : $this->never())
            ->method('decide')
            ->willReturn(true)
        ;

        return $manager;
    }

    /**
     * @return FirewallMap&MockObject
     */
    private function mockFirewallMap(string|null $context): FirewallMap
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
