<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Security\Authentication;

use Contao\BackendUser;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Security\Authentication\AuthenticationSuccessHandler;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Contao\PageModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\HttpUtils;

class AuthenticationSuccessHandlerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $handler = $this->mockSuccessHandler();

        $this->assertInstanceOf('Contao\CoreBundle\Security\Authentication\AuthenticationSuccessHandler', $handler);
    }

    public function testUpdatesTheUser(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $logger
            ->expects($this->once())
            ->method('info')
            ->with('User "foobar" has logged in')
        ;

        $request = $this->createMock(Request::class);

        $request
            ->method('getUriForPath')
            ->willReturn('http://localhost/target')
        ;

        /** @var BackendUser|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->createPartialMock(BackendUser::class, ['save']);
        $user->username = 'foobar';
        $user->lastLogin = time() - 3600;
        $user->currentLogin = time() - 1800;

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $token = $this->createMock(TokenInterface::class);

        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $handler = $this->mockSuccessHandler(null, $logger);
        $response = $handler->onAuthenticationSuccess($request, $token);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame('http://localhost/target', $response->getTargetUrl());
    }

    public function testDoesNotUpdateTheUserIfNotAContaoUser(): void
    {
        $request = $this->createMock(Request::class);

        $request
            ->method('getUriForPath')
            ->willReturn('http://localhost/target')
        ;

        $token = $this->createMock(TokenInterface::class);

        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class))
        ;

        $handler = $this->mockSuccessHandler();
        $response = $handler->onAuthenticationSuccess($request, $token);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame('http://localhost/target', $response->getTargetUrl());
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using the "postLogin" hook has been deprecated %s.
     */
    public function testTriggersThePostLoginHook(): void
    {
        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(__CLASS__)
            ->willReturn($this)
        ;

        $logger = $this->createMock(LoggerInterface::class);

        $logger
            ->expects($this->once())
            ->method('info')
            ->with('User "foobar" has logged in')
        ;

        $request = $this->createMock(Request::class);

        $request
            ->method('getUriForPath')
            ->willReturn('http://localhost/target')
        ;

        /** @var BackendUser|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->createPartialMock(BackendUser::class, ['save']);
        $user->username = 'foobar';
        $user->lastLogin = time() - 3600;
        $user->currentLogin = time() - 1800;

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $token = $this->createMock(TokenInterface::class);

        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $GLOBALS['TL_HOOKS']['postLogin'] = [[__CLASS__, 'onPostLogin']];

        $handler = $this->mockSuccessHandler($framework, $logger);
        $handler->onAuthenticationSuccess($request, $token);

        unset($GLOBALS['TL_HOOKS']);
    }

    /**
     * @param UserInterface $user
     */
    public function onPostLogin(UserInterface $user): void
    {
        $this->assertInstanceOf('Contao\BackendUser', $user);
    }

    public function testUsesTheUrlOfThePage(): void
    {
        $model = $this->createMock(PageModel::class);

        $model
            ->expects($this->once())
            ->method('getAbsoluteUrl')
            ->willReturn('http://localhost/page')
        ;

        $adapter = $this->mockAdapter(['findFirstActiveByMemberGroups']);

        $adapter
            ->expects($this->once())
            ->method('findFirstActiveByMemberGroups')
            ->with([2, 3])
            ->willReturn($model)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        /** @var FrontendUser|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->createPartialMock(FrontendUser::class, ['save']);
        $user->lastLogin = time() - 3600;
        $user->currentLogin = time() - 1800;
        $user->groups = serialize([2, 3]);

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $token = $this->createMock(TokenInterface::class);

        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        $handler = $this->mockSuccessHandler($framework);
        $response = $handler->onAuthenticationSuccess(new Request(), $token);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame('http://localhost/page', $response->getTargetUrl());
    }

    public function testUsesTheDefaultUrlIfNotAPageModel(): void
    {
        $adapter = $this->mockAdapter(['findFirstActiveByMemberGroups']);

        $adapter
            ->expects($this->once())
            ->method('findFirstActiveByMemberGroups')
            ->with([2, 3])
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        $request = new Request();
        $request->attributes->set('_target_path', 'http://localhost/target');

        /** @var FrontendUser|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->createPartialMock(FrontendUser::class, ['save']);
        $user->lastLogin = time() - 3600;
        $user->currentLogin = time() - 1800;
        $user->groups = serialize([2, 3]);

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $token = $this->createMock(TokenInterface::class);

        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        $handler = $this->mockSuccessHandler($framework);
        $response = $handler->onAuthenticationSuccess($request, $token);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame('http://localhost/target', $response->getTargetUrl());
    }

    public function testUsesTheTargetPath(): void
    {
        $adapter = $this->mockAdapter(['findFirstActiveByMemberGroups']);

        $adapter
            ->expects($this->never())
            ->method('findFirstActiveByMemberGroups')
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        $request = new Request();
        $request->request->set('_target_path', 'http://localhost/target');
        $request->request->set('_always_use_target_path', '1');

        /** @var FrontendUser|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->createPartialMock(FrontendUser::class, ['save']);
        $user->lastLogin = time() - 3600;
        $user->currentLogin = time() - 1800;
        $user->groups = serialize([2, 3]);

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $token = $this->createMock(TokenInterface::class);

        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        $handler = $this->mockSuccessHandler($framework);
        $response = $handler->onAuthenticationSuccess($request, $token);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame('http://localhost/target', $response->getTargetUrl());
    }

    /**
     * Mocks an authentication success handler.
     *
     * @param ContaoFrameworkInterface|null $framework
     * @param LoggerInterface|null          $logger
     *
     * @return AuthenticationSuccessHandler
     */
    private function mockSuccessHandler(ContaoFrameworkInterface $framework = null, LoggerInterface $logger = null): AuthenticationSuccessHandler
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $urlGenerator
            ->method('generate')
            ->willReturn('http://localhost')
        ;

        $utils = new HttpUtils($urlGenerator);

        if (null === $framework) {
            $framework = $this->mockContaoFramework();
        }

        if (null === $logger) {
            $logger = $this->createMock(LoggerInterface::class);
        }

        return new AuthenticationSuccessHandler($utils, $framework, $logger);
    }
}
