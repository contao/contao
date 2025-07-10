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
use Contao\CoreBundle\EventListener\DataContainer\SwitchMemberOperationListener;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SwitchMemberOperationTest extends TestCase
{
    public function testOperationIsHiddenIfUserIsNotABackendUser(): void
    {
        $user = $this->createMock(UserInterface::class);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('hide')
        ;

        $listener = new SwitchMemberOperationListener($security, $this->createMock(UrlGeneratorInterface::class));
        $listener($operation);
    }

    public function testOperationIsHiddenIfAllowedMemberGroupsIsNull(): void
    {
        $user = $this->createMock(BackendUser::class);
        $user
            ->method('__get')
            ->willReturnMap([
                ['isAdmin', false],
                ['amg', null],
            ])
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('hide')
        ;

        $listener = new SwitchMemberOperationListener($security, $this->createMock(UrlGeneratorInterface::class));
        $listener($operation);
    }

    public function testOperationIsHiddenIfAllowedMemberGroupsIsEmpty(): void
    {
        $user = $this->createMock(BackendUser::class);
        $user
            ->method('__get')
            ->willReturnMap([
                ['isAdmin', false],
                ['amg', []],
            ])
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('hide')
        ;

        $listener = new SwitchMemberOperationListener($security, $this->createMock(UrlGeneratorInterface::class));
        $listener($operation);
    }

    public function testOperationIsDisabledIfMemberCannotLogin(): void
    {
        $user = $this->createMock(BackendUser::class);
        $user
            ->method('__get')
            ->willReturnMap([
                ['isAdmin', true],
            ])
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->method('getRecord')
            ->willReturn(['login' => 0, 'username' => 'foobar'])
        ;

        $operation
            ->expects($this->once())
            ->method('disable')
        ;

        $listener = new SwitchMemberOperationListener($security, $this->createMock(UrlGeneratorInterface::class));
        $listener($operation);
    }

    public function testOperationIsDisabledIfMemberHasNoUsername(): void
    {
        $user = $this->createMock(BackendUser::class);
        $user
            ->method('__get')
            ->willReturnMap([
                ['isAdmin', true],
            ])
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->method('getRecord')
            ->willReturn(['login' => 1, 'username' => ''])
        ;

        $operation
            ->expects($this->once())
            ->method('disable')
        ;

        $listener = new SwitchMemberOperationListener($security, $this->createMock(UrlGeneratorInterface::class));
        $listener($operation);
    }

    public function testReplacesOperationUrl(): void
    {
        $user = $this->createMock(BackendUser::class);
        $user
            ->method('__get')
            ->willReturnMap([
                ['isAdmin', true],
            ])
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $htmlAttributes = $this->createMock(HtmlAttributes::class);
        $htmlAttributes
            ->expects($this->exactly(2))
            ->method('set')
            ->willReturnSelf()
        ;

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->method('getRecord')
            ->willReturn(['login' => 1, 'username' => 'foobar'])
        ;

        $operation
            ->method('offsetGet')
            ->with('attributes')
            ->willReturn($htmlAttributes)
        ;

        $operation
            ->expects($this->once())
            ->method('setUrl')
            ->with('/url/to/frontend')
        ;

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend_preview', ['user' => 'foobar'])
            ->willReturn('/url/to/frontend')
        ;

        $listener = new SwitchMemberOperationListener($security, $urlGenerator);
        $listener($operation);
    }
}
