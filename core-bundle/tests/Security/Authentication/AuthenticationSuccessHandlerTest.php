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

use Contao\BackendUser;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\HttpKernel\JwtManager;
use Contao\CoreBundle\Security\Authentication\AuthenticationSuccessHandler;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\System;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\HttpUtils;

class AuthenticationSuccessHandlerTest extends TestCase
{
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

        $request->attributes = new ParameterBag();

        /** @var BackendUser|MockObject $user */
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

        $this->assertSame('http://localhost/target', $response->getTargetUrl());
    }

    public function testDoesNotUpdateTheUserIfNotAContaoUser(): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->method('getUriForPath')
            ->willReturn('http://localhost/target')
        ;

        $request->attributes = new ParameterBag();

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class))
        ;

        $handler = $this->mockSuccessHandler();
        $response = $handler->onAuthenticationSuccess($request, $token);

        $this->assertSame('http://localhost/target', $response->getTargetUrl());
    }

    public function testAddsTheJwtCookieForTheBackendUser(): void
    {
        $jwtManager = $this->createMock(JwtManager::class);
        $jwtManager
            ->expects($this->once())
            ->method('addResponseCookie')
        ;

        $request = new Request();
        $request->attributes->set(JwtManager::ATTRIBUTE, $jwtManager);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(BackendUser::class))
        ;

        $handler = $this->mockSuccessHandler();
        $handler->onAuthenticationSuccess($request, $token);
    }

    public function testDoesNotAddTheJwtCookieForTheFrontendUser(): void
    {
        $jwtManager = $this->createMock(JwtManager::class);
        $jwtManager
            ->expects($this->never())
            ->method('addResponseCookie')
        ;

        $request = new Request();
        $request->attributes->set(JwtManager::ATTRIBUTE, $jwtManager);

        $adapter = $this->mockAdapter(['findFirstActiveByMemberGroups']);
        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(FrontendUser::class))
        ;

        $handler = $this->mockSuccessHandler($framework);
        $handler->onAuthenticationSuccess($request, $token);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using the "postLogin" hook has been deprecated %s.
     */
    public function testTriggersThePostLoginHook(): void
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

        $request->attributes = new ParameterBag();

        /** @var BackendUser|MockObject $user */
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

        $listener = $this->createPartialMock(Controller::class, ['onPostLogin']);
        $listener
            ->expects($this->once())
            ->method('onPostLogin')
            ->with($user)
        ;

        $systemAdapter = $this->mockAdapter(['importStatic']);
        $systemAdapter
            ->expects($this->once())
            ->method('importStatic')
            ->with('HookListener')
            ->willReturn($listener)
        ;

        $framework = $this->mockContaoFramework([System::class => $systemAdapter]);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $GLOBALS['TL_HOOKS']['postLogin'] = [['HookListener', 'onPostLogin']];

        $handler = $this->mockSuccessHandler($framework, $logger);
        $handler->onAuthenticationSuccess($request, $token);

        unset($GLOBALS['TL_HOOKS']);
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

        /** @var FrontendUser|MockObject $user */
        $user = $this->createPartialMock(FrontendUser::class, ['save']);
        $user->lastLogin = time() - 3600;
        $user->currentLogin = time() - 1800;
        $user->groups = [2, 3];

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

        /** @var FrontendUser|MockObject $user */
        $user = $this->createPartialMock(FrontendUser::class, ['save']);
        $user->lastLogin = time() - 3600;
        $user->currentLogin = time() - 1800;
        $user->groups = [2, 3];

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

        /** @var FrontendUser|MockObject $user */
        $user = $this->createPartialMock(FrontendUser::class, ['save']);
        $user->lastLogin = time() - 3600;
        $user->currentLogin = time() - 1800;
        $user->groups = [2, 3];

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

        $this->assertSame('http://localhost/target', $response->getTargetUrl());
    }

    private function mockSuccessHandler(ContaoFramework $framework = null, LoggerInterface $logger = null): AuthenticationSuccessHandler
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
