<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Tests\EventListener;

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Exception\NotFoundException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\DataContainer;
use Contao\NewsletterBundle\EventListener\BlockRecipientListener;
use Contao\NewsletterDenyListModel;
use Contao\NewsletterRecipientsModel;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

class BlockRecipientListenerTest extends ContaoTestCase
{
    public function testHidesTheOperationIfRecipientCannotBeDeleted(): void
    {
        $record = ['foo' => 'bar'];

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with(
                ContaoCorePermissions::DC_PREFIX.'tl_newsletter_recipients',
                new DeleteAction('tl_newsletter_recipients', $record),
            )
            ->willReturn(false)
        ;

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->method('getRecord')
            ->willReturn($record)
        ;

        $operation
            ->expects($this->once())
            ->method('hide')
        ;

        $listener = new BlockRecipientListener(
            $this->createStub(ContaoFramework::class),
            $security,
            $this->createStub(RequestStack::class),
            $this->createStub(RouterInterface::class),
            $this->createStub(ContaoCsrfTokenManager::class),
            '_contao_csrf',
        );

        $listener->onButton($operation);
    }

    public function testThrowsExceptionOnInvalidRequestToken(): void
    {
        $this->expectException(InvalidRequestTokenException::class);

        $requestStack = new RequestStack();
        $requestStack->push(Request::create('https://localhost/contao', parameters: ['rt' => 'foo']));

        $csrfTokenManager = $this->createMock(ContaoCsrfTokenManager::class);
        $csrfTokenManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->with($this->callback(static fn (CsrfToken $token) => '_contao_csrf' === $token->getId() && 'foo' === $token->getValue()))
            ->willReturn(false)
        ;

        $listener = new BlockRecipientListener(
            $this->createStub(ContaoFramework::class),
            $this->createStub(Security::class),
            $requestStack,
            $this->createStub(RouterInterface::class),
            $csrfTokenManager,
            '_contao_csrf',
        );

        $listener->blockRecipient($this->createStub(DataContainer::class));
    }

    public function testThrowsExceptionIfRecipientIsNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $recipientAdapter = $this->createAdapterMock(['findById']);
        $recipientAdapter
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null)
        ;

        $framework = $this->createContaoFrameworkStub([
            NewsletterRecipientsModel::class => $recipientAdapter,
        ]);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $csrfTokenManager = $this->createStub(ContaoCsrfTokenManager::class);
        $csrfTokenManager
            ->method('isTokenValid')
            ->willReturn(true)
        ;

        $listener = new BlockRecipientListener(
            $framework,
            $security,
            $this->createStub(RequestStack::class),
            $this->createStub(RouterInterface::class),
            $csrfTokenManager,
            '_contao_csrf',
        );

        $listener->blockRecipient($this->createStub(DataContainer::class));
    }

    public function testThrowsExceptionIfDeleteIsNotGranted(): void
    {
        $this->expectException(AccessDeniedException::class);

        $recipientAdapter = $this->createAdapterMock(['findById']);
        $recipientAdapter
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->createClassWithPropertiesStub(NewsletterRecipientsModel::class))
        ;

        $framework = $this->createContaoFrameworkStub([
            NewsletterRecipientsModel::class => $recipientAdapter,
        ]);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(false)
        ;

        $csrfTokenManager = $this->createStub(ContaoCsrfTokenManager::class);
        $csrfTokenManager
            ->method('isTokenValid')
            ->willReturn(true)
        ;

        $listener = new BlockRecipientListener(
            $framework,
            $security,
            $this->createStub(RequestStack::class),
            $this->createStub(RouterInterface::class),
            $csrfTokenManager,
            '_contao_csrf',
        );

        $listener->blockRecipient($this->createStub(DataContainer::class));
    }

    public function testDoesNotAddToDenyListIfRecipientExists(): void
    {
        $this->expectException(RedirectResponseException::class);

        $recipientAdapter = $this->createAdapterMock(['findById']);
        $recipientAdapter
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->createClassWithPropertiesStub(NewsletterRecipientsModel::class, ['pid' => 42, 'email' => 'k.jones@example.org']))
        ;

        $denyListAdapter = $this->createAdapterMock(['findByHashAndPid']);
        $denyListAdapter
            ->expects($this->once())
            ->method('findByHashAndPid')
            ->with(md5('k.jones@example.org'), 42)
            ->willReturn($this->createClassWithPropertiesStub(NewsletterDenyListModel::class))
        ;

        $systemAdapter = $this->createAdapterStub(['getReferer']);
        $systemAdapter
            ->method('getReferer')
            ->willReturn('https://localhost/contao')
        ;

        $framework = $this->createContaoFrameworkMock([
            NewsletterRecipientsModel::class => $recipientAdapter,
            NewsletterDenyListModel::class => $denyListAdapter,
            System::class => $systemAdapter,
        ]);

        $framework
            ->expects($this->never())
            ->method('createInstance')
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(true)
        ;

        $csrfTokenManager = $this->createStub(ContaoCsrfTokenManager::class);
        $csrfTokenManager
            ->method('isTokenValid')
            ->willReturn(true)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->willReturn('https://localhost/contao?do=newsletter&id=1&table=tl_newsletter_recipients')
        ;

        $listener = new BlockRecipientListener(
            $framework,
            $security,
            $this->createStub(RequestStack::class),
            $router,
            $csrfTokenManager,
            '_contao_csrf',
        );

        $listener->blockRecipient($this->createStub(DataContainer::class));
    }

    public function testAddsToDenyListIfRecipientDoesNotExist(): void
    {
        $this->expectException(RedirectResponseException::class);

        $recipientAdapter = $this->createAdapterMock(['findById']);
        $recipientAdapter
            ->expects($this->once())
            ->method('findById')
            ->willReturn($this->createClassWithPropertiesStub(NewsletterRecipientsModel::class, ['pid' => 42, 'email' => 'k.jones@example.org']))
        ;

        $denyListAdapter = $this->createAdapterMock(['findByHashAndPid']);
        $denyListAdapter
            ->expects($this->once())
            ->method('findByHashAndPid')
            ->with(md5('k.jones@example.org'), 42)
            ->willReturn(null)
        ;

        $systemAdapter = $this->createAdapterStub(['getReferer']);
        $systemAdapter
            ->method('getReferer')
            ->willReturn('https://localhost/contao')
        ;

        $denyListModel = $this->createClassWithPropertiesMock(NewsletterDenyListModel::class);
        $denyListModel
            ->expects($this->once())
            ->method('save')
        ;

        $framework = $this->createContaoFrameworkStub(
            [
                NewsletterRecipientsModel::class => $recipientAdapter,
                NewsletterDenyListModel::class => $denyListAdapter,
                System::class => $systemAdapter,
            ],
            [
                NewsletterDenyListModel::class => $denyListModel,
            ],
        );

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(true)
        ;

        $csrfTokenManager = $this->createStub(ContaoCsrfTokenManager::class);
        $csrfTokenManager
            ->method('isTokenValid')
            ->willReturn(true)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->willReturn('https://localhost/contao?do=newsletter&id=1&table=tl_newsletter_recipients')
        ;

        $listener = new BlockRecipientListener(
            $framework,
            $security,
            $this->createStub(RequestStack::class),
            $router,
            $csrfTokenManager,
            '_contao_csrf',
        );

        $listener->blockRecipient($this->createStub(DataContainer::class));
    }
}
