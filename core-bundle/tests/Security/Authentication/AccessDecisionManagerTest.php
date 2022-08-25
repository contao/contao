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
use Symfony\Bundle\SecurityBundle\Security\FirewallContext;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class AccessDecisionManagerTest extends TestCase
{
    public function testUsesOriginalDecisionManagerIfNotContaoFirewall(): void
    {
        $accessDecisionManager = new AccessDecisionManager(
            $this->mockAccessDecisionManager(true),
            $this->mockAccessDecisionManager(false),
            $this->mockFirewallContext('foobar')
        );

        $accessDecisionManager->decide($this->createMock(TokenInterface::class), []);
    }

    public function testUsesContaoDecisionManagerIfContaoBackendFirewall(): void
    {
        $accessDecisionManager = new AccessDecisionManager(
            $this->mockAccessDecisionManager(false),
            $this->mockAccessDecisionManager(true),
            $this->mockFirewallContext('contao_backend')
        );

        $accessDecisionManager->decide($this->createMock(TokenInterface::class), []);
    }

    public function testUsesContaoDecisionManagerIfContaoFrontendFirewall(): void
    {
        $accessDecisionManager = new AccessDecisionManager(
            $this->mockAccessDecisionManager(false),
            $this->mockAccessDecisionManager(true),
            $this->mockFirewallContext('contao_frontend')
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

    private function mockFirewallContext(string $name)
    {
        $context = $this->createMock(FirewallContext::class);

        $context
            ->method('getConfig')
            ->willReturn(new FirewallConfig($name, ''))
        ;

        return $context;
    }
}
