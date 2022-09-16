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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\Authentication\AuthenticationSuccessHandler;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Contao\PageModel;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthenticationSuccessHandlerTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testUpdatesTheUserAndAlwaysRedirectsToTargetPathInBackend(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('User "foobar" has logged in')
        ;

        $parameters = [
            '_always_use_target_path' => '0',
            '_target_path' => base64_encode('http://localhost/target'),
        ];

        $request = new Request([], $parameters);

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

        $handler = $this->getHandler(null, $logger);
        $response = $handler->onAuthenticationSuccess($request, $token);

        $this->assertSame('http://localhost/target', $response->getTargetUrl());
    }

    public function testThrowsExceptionIfTargetPathParameterIsMissing(): void
    {
        $parameters = [
            '_always_use_target_path' => '0',
        ];

        $request = new Request([], $parameters);

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

        $this->expectException(BadRequestHttpException::class);

        $handler = $this->getHandler();
        $handler->onAuthenticationSuccess($request, $token);
    }

    public function testDoesNotUpdateTheUserIfNotAContaoUser(): void
    {
        $parameters = [
            '_always_use_target_path' => '1',
            '_target_path' => base64_encode('http://localhost/target'),
        ];

        $request = new Request([], $parameters);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class))
        ;

        $handler = $this->getHandler();
        $response = $handler->onAuthenticationSuccess($request, $token);

        $this->assertSame('http://localhost/target', $response->getTargetUrl());
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

        $handler = $this->getHandler($framework);
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

        $parameters = [
            '_always_use_target_path' => '0',
            '_target_path' => base64_encode('http://localhost/target'),
        ];

        $request = new Request([], $parameters);

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

        $handler = $this->getHandler($framework);
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

        $parameters = [
            '_always_use_target_path' => '1',
            '_target_path' => base64_encode('http://localhost/target'),
        ];

        $request = new Request([], $parameters);

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

        $handler = $this->getHandler($framework);
        $response = $handler->onAuthenticationSuccess($request, $token);

        $this->assertSame('http://localhost/target', $response->getTargetUrl());
    }

    public function testReloadsIfTwoFactorAuthenticationIsEnabled(): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getUri')
            ->willReturn('http://localhost/failure')
        ;

        $user = $this->createPartialMock(FrontendUser::class, ['save']);
        $user
            ->expects($this->once())
            ->method('save')
        ;

        $token = $this->createMock(TwoFactorToken::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $response = $this->getHandler()->onAuthenticationSuccess($request, $token);

        $this->assertSame('http://localhost/failure', $response->getTargetUrl());
    }

    public function testStoresTheTargetPathInSessionOnTwoFactorAuthentication(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('set')
            ->with('_security.contao_frontend.target_path')
        ;

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->atLeastOnce())
            ->method('getUri')
            ->willReturn('http://localhost/failure')
        ;

        $request
            ->method('getSession')
            ->willReturn($session)
        ;

        $request
            ->method('hasSession')
            ->willReturn(true)
        ;

        $request
            ->method('isMethodSafe')
            ->willReturn(true)
        ;

        $request
            ->method('isXmlHttpRequest')
            ->willReturn(false)
        ;

        $user = $this->createPartialMock(FrontendUser::class, ['save']);

        $token = $this->createMock(TwoFactorToken::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $token
            ->expects($this->once())
            ->method('getFirewallName')
            ->willReturn('contao_frontend')
        ;

        $response = $this->getHandler()->onAuthenticationSuccess($request, $token);

        $this->assertSame('http://localhost/failure', $response->getTargetUrl());
    }

    public function testRemovesTheTargetPathInTheSessionOnLogin(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('remove')
            ->with('_security.contao_frontend.target_path')
        ;

        $request = new Request([], ['_target_path' => base64_encode('/')]);
        $request->setSession($session);

        $token = $this->createMock(UsernamePasswordToken::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createPartialMock(BackendUser::class, ['save']))
        ;

        $token
            ->expects($this->once())
            ->method('getFirewallName')
            ->willReturn('contao_frontend')
        ;

        $this->getHandler()->onAuthenticationSuccess($request, $token);
    }

    private function getHandler(ContaoFramework $framework = null, LoggerInterface $logger = null): AuthenticationSuccessHandler
    {
        $framework ??= $this->mockContaoFramework();
        $trustedDeviceManager = $this->createMock(TrustedDeviceManagerInterface::class);
        $firewallMap = $this->createMock(FirewallMap::class);
        $logger ??= $this->createMock(LoggerInterface::class);

        return new AuthenticationSuccessHandler($framework, $trustedDeviceManager, $firewallMap, $logger);
    }
}
