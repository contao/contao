<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\Page;

use Contao\BackendUser;
use Contao\CoreBundle\Controller\Page\LogoutPageController;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;

class LogoutPageControllerTest extends TestCase
{
    public function testRedirectsGuestToTheReferer(): void
    {
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, ['redirectBack' => true, 'jumpTo' => 0]);

        $systemAdapter = $this->createAdapterMock(['getReferer']);
        $systemAdapter
            ->expects($this->once())
            ->method('getReferer')
            ->willReturn('https://example.org')
        ;

        $framework = $this->createContaoFrameworkStub([
            System::class => $systemAdapter,
        ]);

        $tokenStorage = $this->createStub(TokenStorageInterface::class);

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);
        $container->set('security.token_storage', $tokenStorage);

        $logoutUrlGenerator = $this->createMock(LogoutUrlGenerator::class);
        $logoutUrlGenerator
            ->expects($this->never())
            ->method('getLogoutUrl')
        ;

        $controller = new LogoutPageController($logoutUrlGenerator);
        $controller->setContainer($container);

        $response = $controller(new Request(), $pageModel);

        $this->assertSame('https://example.org', $response->getTargetUrl());
    }

    public function testRedirectsGuestToTheJumpToPage(): void
    {
        $forwardPageModel = $this->createClassWithPropertiesStub(PageModel::class);

        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, ['redirectBack' => false, 'jumpTo' => 42]);
        $pageModel
            ->method('getRelated')
            ->willReturn($forwardPageModel)
        ;

        $systemAdapter = $this->createAdapterMock(['getReferer']);
        $systemAdapter
            ->expects($this->never())
            ->method('getReferer')
        ;

        $framework = $this->createContaoFrameworkStub([
            System::class => $systemAdapter,
        ]);

        $tokenStorage = $this->createStub(TokenStorageInterface::class);

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($forwardPageModel)
            ->willReturn('https://example.org')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);
        $container->set('security.token_storage', $tokenStorage);
        $container->set('contao.routing.content_url_generator', $contentUrlGenerator);

        $logoutUrlGenerator = $this->createMock(LogoutUrlGenerator::class);
        $logoutUrlGenerator
            ->expects($this->never())
            ->method('getLogoutUrl')
        ;

        $controller = new LogoutPageController($logoutUrlGenerator);
        $controller->setContainer($container);

        $response = $controller(new Request(), $pageModel);

        $this->assertSame('https://example.org', $response->getTargetUrl());
    }

    public function testRedirectsMemberToTheLogoutUrlWithRedirectReferer(): void
    {
        $systemAdapter = $this->createAdapterMock(['getReferer']);
        $systemAdapter
            ->expects($this->once())
            ->method('getReferer')
            ->willReturn('https://example.org')
        ;

        $framework = $this->createContaoFrameworkStub([
            System::class => $systemAdapter,
        ]);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($this->createStub(BackendUser::class))
        ;

        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;
        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);
        $container->set('security.token_storage', $tokenStorage);

        $logoutUrlGenerator = $this->createMock(LogoutUrlGenerator::class);
        $logoutUrlGenerator
            ->expects($this->once())
            ->method('getLogoutUrl')
            ->willReturn('https://example.org/foo/bar')
        ;

        $controller = new LogoutPageController($logoutUrlGenerator);
        $controller->setContainer($container);

        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, ['redirectBack' => true, 'jumpTo' => 0]);

        $response = $controller(new Request(), $pageModel);

        $this->assertSame('https://example.org/foo/bar?redirect='.urlencode('https://example.org'), $response->getTargetUrl());
    }

    public function testRedirectsMemberToTheLogoutUrlWithRedirectJumpTo(): void
    {
        $forwardPageModel = $this->createClassWithPropertiesStub(PageModel::class);

        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, ['redirectBack' => false, 'jumpTo' => 42]);
        $pageModel
            ->method('getRelated')
            ->willReturn($forwardPageModel)
        ;

        $systemAdapter = $this->createAdapterMock(['getReferer']);
        $systemAdapter
            ->expects($this->never())
            ->method('getReferer')
        ;

        $framework = $this->createContaoFrameworkStub([
            System::class => $systemAdapter,
        ]);

        $token = $this->createStub(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($this->createStub(BackendUser::class))
        ;

        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($forwardPageModel)
            ->willReturn('https://example.org')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);
        $container->set('security.token_storage', $tokenStorage);
        $container->set('contao.routing.content_url_generator', $contentUrlGenerator);

        $logoutUrlGenerator = $this->createMock(LogoutUrlGenerator::class);
        $logoutUrlGenerator
            ->expects($this->once())
            ->method('getLogoutUrl')
            ->willReturn('https://example.org/foo/bar')
        ;

        $controller = new LogoutPageController($logoutUrlGenerator);
        $controller->setContainer($container);

        $response = $controller(new Request(), $pageModel);

        $this->assertSame('https://example.org/foo/bar?redirect='.urlencode('https://example.org'), $response->getTargetUrl());
    }
}
