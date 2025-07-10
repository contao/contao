<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\BackendUser;
use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\EventListener\DataContainer\SwitchUserOperationListener;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SwitchUserOperationTest extends TestCase
{
    public function testOperationIsHiddenIfUserIsNotAllowedToSwitch(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ALLOWED_TO_SWITCH')
            ->willReturn(false)
        ;

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('hide')
        ;

        $listener = new SwitchUserOperationListener($security, $this->createMock(UrlGeneratorInterface::class));
        $listener($operation);
    }

    public function testOperationIsDisabledForCurrentUser(): void
    {
        $user = $this->createMock(BackendUser::class);
        $user
            ->method('__get')
            ->with('id')
            ->willReturn(42)
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ALLOWED_TO_SWITCH')
            ->willReturn(true)
        ;

        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('getRecord')
            ->willReturn(['id' => 42, 'username' => 'foobar'])
        ;

        $operation
            ->expects($this->once())
            ->method('disable')
        ;

        $listener = new SwitchUserOperationListener($security, $this->createMock(UrlGeneratorInterface::class));
        $listener($operation);
    }

    public function testOperationIsDisabledIfCurrentUserIsImpersonating(): void
    {
        $user = $this->createMock(BackendUser::class);
        $user
            ->method('__get')
            ->with('id')
            ->willReturn(21)
        ;

        $originalUser = $this->createMock(BackendUser::class);
        $originalUser
            ->method('__get')
            ->with('id')
            ->willReturn(42)
        ;

        $originalToken = $this->createMock(TokenInterface::class);
        $originalToken
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($originalUser)
        ;

        $switchUserToken = $this->createMock(SwitchUserToken::class);
        $switchUserToken
            ->expects($this->once())
            ->method('getOriginalToken')
            ->willReturn($originalToken)
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ALLOWED_TO_SWITCH')
            ->willReturn(true)
        ;

        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $security
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($switchUserToken)
        ;

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('getRecord')
            ->willReturn(['id' => 42, 'username' => 'foobar'])
        ;

        $operation
            ->expects($this->once())
            ->method('disable')
        ;

        $listener = new SwitchUserOperationListener($security, $this->createMock(UrlGeneratorInterface::class));
        $listener($operation);
    }

    public function testReplacesTheOperationUrl(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ALLOWED_TO_SWITCH')
            ->willReturn(true)
        ;

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('getRecord')
            ->willReturn(['id' => 42, 'username' => 'foobar'])
        ;

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend', ['_switch_user' => 'foobar'])
            ->willReturn('/url/to/switch')
        ;

        $htmlAttributes = $this->createMock(HtmlAttributes::class);
        $htmlAttributes
            ->expects($this->once())
            ->method('set')
            ->with('data-turbo-prefetch', 'false')
            ->willReturnSelf()
        ;

        $operation
            ->expects($this->once())
            ->method('setUrl')
            ->with('/url/to/switch')
        ;

        $operation
            ->method('offsetGet')
            ->with('attributes')
            ->willReturn($htmlAttributes)
        ;

        $listener = new SwitchUserOperationListener($security, $urlGenerator);
        $listener($operation);
    }
}
