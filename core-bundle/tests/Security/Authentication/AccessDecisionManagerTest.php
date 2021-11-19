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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class AccessDecisionManagerTest extends TestCase
{
    public function testLeavesOriginalConfigurationUntouchedIfNoRequestAvailable(): void
    {
        $inner = $this->createAccessDecisionManager(true);
        $contao = $this->createAccessDecisionManager(false);

        $requestStack = new RequestStack();

        $accessDecisionManager = new AccessDecisionManager(
            $inner,
            $contao,
            $this->mockScopeMatcher(),
            $requestStack
        );

        $accessDecisionManager->decide($this->createMock(TokenInterface::class), []);
    }

    public function testLeavesOriginalConfigurationUntouchedIfNotContaoScope(): void
    {
        $inner = $this->createAccessDecisionManager(true);
        $contao = $this->createAccessDecisionManager(false);

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $accessDecisionManager = new AccessDecisionManager(
            $inner,
            $contao,
            $this->mockScopeMatcher(),
            $requestStack
        );

        $accessDecisionManager->decide($this->createMock(TokenInterface::class), []);
    }

    public function testUsesContaoDecisionManagerIfContaoRequest(): void
    {
        $inner = $this->createAccessDecisionManager(false);
        $contao = $this->createAccessDecisionManager(true);

        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [], ['_scope' => 'frontend']));

        $accessDecisionManager = new AccessDecisionManager(
            $inner,
            $contao,
            $this->mockScopeMatcher(),
            $requestStack
        );

        $accessDecisionManager->decide($this->createMock(TokenInterface::class), []);
    }

    private function createAccessDecisionManager(bool $shouldBeCalled)
    {
        $manager = $this->createMock(AccessDecisionManagerInterface::class);
        $manager
            ->expects($shouldBeCalled ? $this->once() : $this->never())
            ->method('decide')
        ;

        return $manager;
    }
}
