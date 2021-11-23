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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class AccessDecisionManagerTest extends TestCase
{
    public function testLeavesOriginalConfigurationUntouchedIfNoRequestAvailable(): void
    {
        $accessDecisionManager = new AccessDecisionManager(
            $this->mockAccessDecisionManager(true),
            $this->mockAccessDecisionManager(false),
            $this->mockScopeMatcher(),
            new RequestStack()
        );

        $accessDecisionManager->decide($this->createMock(TokenInterface::class), []);
    }

    public function testLeavesOriginalConfigurationUntouchedIfNotContaoScope(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $accessDecisionManager = new AccessDecisionManager(
            $this->mockAccessDecisionManager(true),
            $this->mockAccessDecisionManager(false),
            $this->mockScopeMatcher(),
            $requestStack
        );

        $accessDecisionManager->decide($this->createMock(TokenInterface::class), []);
    }

    public function testLeavesOriginalConfigurationUntouchedIfNotMainRequest(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());
        $requestStack->push(new Request([], [], ['_scope' => 'frontend']));

        $accessDecisionManager = new AccessDecisionManager(
            $this->mockAccessDecisionManager(true),
            $this->mockAccessDecisionManager(false),
            $this->mockScopeMatcher(),
            $requestStack
        );

        $accessDecisionManager->decide($this->createMock(TokenInterface::class), []);
    }

    public function testUsesContaoDecisionManagerIfContaoRequest(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [], ['_scope' => 'frontend']));

        $accessDecisionManager = new AccessDecisionManager(
            $this->mockAccessDecisionManager(false),
            $this->mockAccessDecisionManager(true),
            $this->mockScopeMatcher(),
            $requestStack
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
}
