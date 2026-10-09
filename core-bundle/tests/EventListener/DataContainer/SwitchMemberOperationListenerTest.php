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

use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\EventListener\DataContainer\SwitchMemberOperationListener;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SwitchMemberOperationListenerTest extends TestCase
{
    public function testOperationIsHiddenIfUserDoesNotHaveSwitchMemberRole(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ALLOWED_TO_SWITCH_MEMBER')
            ->willReturn(false)
        ;

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('hide')
        ;

        $listener = new SwitchMemberOperationListener($security, $this->createStub(UrlGeneratorInterface::class));
        $listener($operation);
    }

    public function testOperationIsDisabledIfMemberCannotLogin(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ALLOWED_TO_SWITCH_MEMBER')
            ->willReturn(true)
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

        $listener = new SwitchMemberOperationListener($security, $this->createStub(UrlGeneratorInterface::class));
        $listener($operation);
    }

    public function testOperationIsDisabledIfMemberHasNoUsername(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ALLOWED_TO_SWITCH_MEMBER')
            ->willReturn(true)
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

        $listener = new SwitchMemberOperationListener($security, $this->createStub(UrlGeneratorInterface::class));
        $listener($operation);
    }

    public function testOperationIsDisabledIfUserDoesNotHaveAllowedMemberGroups(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->exactly(2))
            ->method('isGranted')
            ->willReturnMap([
                ['ROLE_ALLOWED_TO_SWITCH_MEMBER', true],
                [ContaoCorePermissions::USER_CAN_USE_MEMBER_GROUP_IN_PREVIEW, ['42'], false],
            ])
        ;

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->method('getRecord')
            ->willReturn(['login' => 1, 'username' => 'foobar', 'groups' => serialize(['42'])])
        ;

        $operation
            ->expects($this->once())
            ->method('disable')
        ;

        $listener = new SwitchMemberOperationListener($security, $this->createStub(UrlGeneratorInterface::class));
        $listener($operation);
    }

    public function testReplacesOperationUrl(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->exactly(2))
            ->method('isGranted')
            ->willReturnMap([
                ['ROLE_ALLOWED_TO_SWITCH_MEMBER', true],
                [ContaoCorePermissions::USER_CAN_USE_MEMBER_GROUP_IN_PREVIEW, ['42'], true],
            ])
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
            ->willReturn(['login' => 1, 'username' => 'foobar', 'groups' => serialize(['42'])])
        ;

        $operation
            ->expects($this->once())
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
