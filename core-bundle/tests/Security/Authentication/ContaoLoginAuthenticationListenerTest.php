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

use Contao\CoreBundle\Security\Authentication\ContaoLoginAuthenticationListener;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenFactoryInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;

class ContaoLoginAuthenticationListenerTest extends TestCase
{
    /**
     * @dataProvider requiresAuthenticationProvider
     */
    public function testRequiresAuthentication(bool $isPost, ?string $formSubmit, bool $requiresAuthentication): void
    {
        $request = $this->mockRequest($isPost);

        if (null !== $formSubmit) {
            $request->request->set('FORM_SUBMIT', $formSubmit);
            $request->request->set('username', 'foo');
            $request->request->set('password', 'foobar');
        }

        $authenticationManager = $this->mockAuthenticationManager($requiresAuthentication ? 'foo' : null, 'foobar');

        $listener = $this->createListener($authenticationManager);
        $listener($this->mockRequestEvent($request));
    }

    public function requiresAuthenticationProvider(): \Generator
    {
        yield 'authentication in backend' => [true, 'tl_login', true];
        yield 'authentication in frontend' => [true, 'tl_login_8', true];
        yield 'no authentication without POST' => [false, 'tl_login', false];
        yield 'no authentication without form submit' => [true, null, false];
        yield 'no authentication with invalid form submit' => [true, 'tl_foobar', false];
    }

    public function testThrowsExceptionIfUsernameIsNotAString(): void
    {
        $request = $this->mockRequest();
        $request->request->set('FORM_SUBMIT', 'tl_login');
        $request->request->set('username', ['foo']);
        $request->request->set('password', 'foobar');

        $authenticationManager = $this->mockAuthenticationManager(null);
        $listener = $this->createListener($authenticationManager);

        $this->expectException(BadRequestHttpException::class);

        $listener($this->mockRequestEvent($request));
    }

    public function testTrimsTheUsername(): void
    {
        $request = $this->mockRequest();
        $request->request->set('FORM_SUBMIT', 'tl_login');
        $request->request->set('username', ' foo ');
        $request->request->set('password', 'foobar');

        $authenticationManager = $this->mockAuthenticationManager('foo', 'foobar');

        $listener = $this->createListener($authenticationManager);
        $listener($this->mockRequestEvent($request));
    }

    public function testFailsAuthenticationIfUsernameIsTooLong(): void
    {
        $request = $this->mockRequest();
        $request->request->set('FORM_SUBMIT', 'tl_login');
        $request->request->set('username', implode('', array_fill(0, 4097, 'a')));
        $request->request->set('password', 'foobar');

        $authenticationManager = $this->mockAuthenticationManager(null);

        $listener = $this->createListener($authenticationManager);
        $listener($this->mockRequestEvent($request));
    }

    public function testStoresLastUsernameInSession(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('set')
            ->with(Security::LAST_USERNAME, 'foo')
        ;

        $request = $this->mockRequest(true, $session);
        $request->request->set('FORM_SUBMIT', 'tl_login');
        $request->request->set('username', 'foo');
        $request->request->set('password', 'foobar');

        $authenticationManager = $this->mockAuthenticationManager('foo', 'foobar');

        $listener = $this->createListener($authenticationManager);
        $listener($this->mockRequestEvent($request));
    }

    public function testHandlesTwoFactorAuthentication(): void
    {
        $authenticatedToken = $this->createMock(UsernamePasswordToken::class);

        $currentToken = $this->createMock(TwoFactorTokenInterface::class);
        $currentToken
            ->expects($this->once())
            ->method('getAuthenticatedToken')
            ->willReturn($authenticatedToken)
        ;

        $currentToken
            ->expects($this->once())
            ->method('getAttributes')
            ->willReturn(['foo'])
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($currentToken)
        ;

        $newToken = $this->createMock(TwoFactorTokenInterface::class);
        $newToken
            ->expects($this->once())
            ->method('setAttributes')
            ->with(['foo'])
        ;

        $twoFactorTokenFactory = $this->createMock(TwoFactorTokenFactoryInterface::class);
        $twoFactorTokenFactory
            ->expects($this->once())
            ->method('create')
            ->with($authenticatedToken, '123456', 'provider_key', [])
            ->willReturn($newToken)
        ;

        $authenticationManager = $this->createMock(AuthenticationManagerInterface::class);
        $authenticationManager
            ->expects($this->once())
            ->method('authenticate')
            ->with($newToken)
            ->willReturn(null)
        ;

        $request = $this->mockRequest();
        $request->request->set('FORM_SUBMIT', 'tl_login');
        $request->request->set('verify', '123456');

        $listener = $this->createListener($authenticationManager, $tokenStorage, $twoFactorTokenFactory);
        $listener($this->mockRequestEvent($request));
    }

    private function createListener(AuthenticationManagerInterface $authenticationManager, TokenStorageInterface $tokenStorage = null, TwoFactorTokenFactoryInterface $twoFactorTokenFactory = null): ContaoLoginAuthenticationListener
    {
        $failureHandler = $this->createMock(AuthenticationFailureHandlerInterface::class);
        $failureHandler
            ->method('onAuthenticationFailure')
            ->willReturn(new Response())
        ;

        if (null === $tokenStorage) {
            $tokenStorage = $this->createMock(TokenStorageInterface::class);
        }

        if (null === $twoFactorTokenFactory) {
            $twoFactorTokenFactory = $this->createMock(TwoFactorTokenFactoryInterface::class);
        }

        return new ContaoLoginAuthenticationListener(
            $tokenStorage,
            $authenticationManager,
            $this->createMock(SessionAuthenticationStrategyInterface::class),
            $this->createMock(HttpUtils::class),
            'provider_key',
            $this->createMock(AuthenticationSuccessHandlerInterface::class),
            $failureHandler,
            [], // Options
            $twoFactorTokenFactory,
            null, // Logger
            null // Event Dispatcher
        );
    }

    /**
     * @return Request&MockObject
     */
    private function mockRequest(bool $isPost = true, SessionInterface $session = null): Request
    {
        if (null === $session) {
            $session = $this->createMock(SessionInterface::class);
        }

        $request = $this->createMock(Request::class);
        $request
            ->method('hasPreviousSession')
            ->willReturn(true)
        ;

        $request
            ->method('hasSession')
            ->willReturn(true)
        ;

        $request
            ->method('getSession')
            ->willReturn($session)
        ;

        $request
            ->expects($this->once())
            ->method('isMethod')
            ->with('POST')
            ->willReturn($isPost)
        ;

        $request->request = new ParameterBag();

        return $request;
    }

    private function mockRequestEvent(Request $request): RequestEvent
    {
        return new RequestEvent($this->createMock(KernelInterface::class), $request, KernelInterface::MASTER_REQUEST);
    }

    private function mockAuthenticationManager(?string $username, string $password = null): AuthenticationManagerInterface
    {
        $authenticationManager = $this->createMock(AuthenticationManagerInterface::class);
        $authenticationManager
            ->expects(null === $username ? $this->never() : $this->once())
            ->method('authenticate')
            ->with($this->callback(
                function ($token) use ($username, $password) {
                    /** @var UsernamePasswordToken $token */
                    $this->assertInstanceOf(UsernamePasswordToken::class, $token);
                    $this->assertSame($username, $token->getUser());
                    $this->assertSame($password, $token->getCredentials());

                    return true;
                }
            ))
            ->willReturn(null)
        ;

        return $authenticationManager;
    }
}
