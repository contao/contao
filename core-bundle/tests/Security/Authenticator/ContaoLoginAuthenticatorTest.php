<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Authenticator;

use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\Authenticator\ContaoLoginAuthenticator;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Contao\PageModel;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Credentials\TwoFactorCodeCredentials;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\TwoFactorAuthenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class ContaoLoginAuthenticatorTest extends TestCase
{
    public function testSupportsTheRequest(): void
    {
        $authenticator = $this->getContaoLoginAuthenticator();

        $this->assertFalse($authenticator->supports(new Request()));

        $request = new Request();
        $request->setMethod('POST');

        $this->assertFalse($authenticator->supports($request));

        $request = new Request();
        $request->setMethod('POST');
        $request->request->set('FORM_SUBMIT', 'foobar');

        $this->assertFalse($authenticator->supports($request));

        $request = new Request();
        $request->setMethod('POST');
        $request->request->set('FORM_SUBMIT', 'tl_login');

        $this->assertTrue($authenticator->supports($request));

        $request = new Request();
        $request->setMethod('POST');
        $request->request->set('FORM_SUBMIT', 'tl_login_1');

        $this->assertTrue($authenticator->supports($request));
    }

    public function testIfAuthenticationIsInteractive(): void
    {
        $authenticator = $this->getContaoLoginAuthenticator();

        $this->assertFalse($authenticator->isInteractive());

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $authenticator = $this->getContaoLoginAuthenticator(requestStack: $requestStack);

        $this->assertFalse($authenticator->isInteractive());

        $request = new Request();
        $request->attributes->set('pageModel', $this->createMock(PageModel::class));

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $authenticator = $this->getContaoLoginAuthenticator(requestStack: $requestStack);

        $this->assertTrue($authenticator->isInteractive());
    }

    public function testCallsTheSuccessAndFailureHandlers(): void
    {
        $request = new Request();
        $exception = new AuthenticationException();
        $token = $this->createMock(TokenInterface::class);

        $failureHandler = $this->createMock(AuthenticationFailureHandlerInterface::class);
        $failureHandler
            ->expects($this->once())
            ->method('onAuthenticationFailure')
            ->with($request, $exception)
        ;

        $successHandler = $this->createMock(AuthenticationSuccessHandlerInterface::class);
        $successHandler
            ->expects($this->once())
            ->method('onAuthenticationSuccess')
            ->with($request, $token)
        ;

        $authenticator = $this->getContaoLoginAuthenticator(
            null,
            $successHandler,
            $failureHandler,
        );

        $authenticator->onAuthenticationFailure($request, $exception);
        $authenticator->onAuthenticationSuccess($request, $token, 'firewall');
    }

    public function testTokenCreation(): void
    {
        $authenticator = $this->getContaoLoginAuthenticator();

        $this->assertInstanceOf(
            PostAuthenticationToken::class,
            $authenticator->createToken($this->createMock(Passport::class), 'firewall'),
        );

        $badge = $this->createMock(TwoFactorCodeCredentials::class);
        $badge
            ->expects($this->once())
            ->method('getTwoFactorToken')
            ->willReturn($this->createMock(TwoFactorToken::class))
        ;

        $passport = $this->createMock(Passport::class);
        $passport
            ->expects($this->once())
            ->method('getBadge')
            ->willReturn($badge)
        ;

        $this->assertInstanceOf(
            TwoFactorToken::class,
            $authenticator->createToken($passport, 'firewall'),
        );

        $twoFactorToken = $this->createMock(TwoFactorToken::class);
        $twoFactorToken
            ->expects($this->once())
            ->method('allTwoFactorProvidersAuthenticated')
            ->willReturn(true)
        ;

        $twoFactorToken
            ->expects($this->once())
            ->method('getAuthenticatedToken')
            ->willReturn($this->createMock(PostAuthenticationToken::class))
        ;

        $badge = $this->createMock(TwoFactorCodeCredentials::class);
        $badge
            ->expects($this->once())
            ->method('getTwoFactorToken')
            ->willReturn($twoFactorToken)
        ;

        $passport = $this->createMock(Passport::class);
        $passport
            ->expects($this->once())
            ->method('getBadge')
            ->willReturn($badge)
        ;

        $this->assertInstanceOf(
            PostAuthenticationToken::class,
            $authenticator->createToken($passport, 'firewall'),
        );
    }

    public function testExecutesTheTwoFactorAuthenticator(): void
    {
        $request = new Request();

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(TwoFactorTokenInterface::class))
        ;

        $twoFactorAuthenticator = $this->createMock(TwoFactorAuthenticator::class);
        $twoFactorAuthenticator
            ->expects($this->once())
            ->method('authenticate')
            ->with($request)
            ->willReturn($this->createMock(Passport::class))
        ;

        $authenticator = $this->getContaoLoginAuthenticator(
            tokenStorage: $tokenStorage,
            twoFactorAuthenticator: $twoFactorAuthenticator,
        );

        $authenticator->authenticate($request);
    }

    /**
     * @dataProvider getUserData
     */
    public function testCreatesThePassportOnAuthentication(string|null $username, string|null $exception): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($exception ? $this->never() : $this->once())
            ->method('set')
            ->with(SecurityRequestAttributes::LAST_USERNAME, $username)
        ;

        $request = new Request();
        $request->request->set('username', $username);
        $request->request->set('password', 'kevinjones');
        $request->setSession($session);

        $token = $this->createMock(UsernamePasswordToken::class);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $authenticator = $this->getContaoLoginAuthenticator(
            userProvider: $this->createMock(ContaoUserProvider::class),
            tokenStorage: $tokenStorage,
            options: ['enable_csrf' => true],
        );

        if (null !== $exception) {
            $this->expectException($exception);
        }

        $authenticator->authenticate($request);
    }

    public static function getUserData(): iterable
    {
        $veryLongUsername = str_repeat('k.jones', (int) ceil(UserBadge::MAX_USERNAME_LENGTH / \strlen('k.jones')));

        yield [null, BadRequestHttpException::class];
        yield [$veryLongUsername, BadCredentialsException::class];
        yield ['k.jones', null];
    }

    public function testRedirectsToBackend(): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(true)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend_login', ['redirect' => 'https://example.com/foobar?foo=1'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('url')
        ;

        $uriSigner = $this->createMock(UriSigner::class);
        $uriSigner
            ->expects($this->once())
            ->method('sign')
            ->willReturn('url')
        ;

        $authenticator = $this->getContaoLoginAuthenticator(
            scopeMatcher: $scopeMatcher,
            router: $router,
            uriSigner: $uriSigner,
        );

        $response = $authenticator->start(Request::create('https://example.com/foobar?foo=1'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('url', $response->getTargetUrl());
    }

    public function testDoesNotAddRedirectParameterForContaoBackendRouteWithoutParameters(): void
    {
        $request = Request::create('https://example.com/contao');
        $request->attributes->set('_route', 'contao_backend');

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('remove')
            ->with('_security.contao_backend.target_path')
        ;

        $request->setSession($session);

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(true)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend_login', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('/contao/login')
        ;

        $uriSigner = $this->createMock(UriSigner::class);
        $uriSigner
            ->expects($this->never())
            ->method('sign')
        ;

        $authenticator = $this->getContaoLoginAuthenticator(
            scopeMatcher: $scopeMatcher,
            router: $router,
            uriSigner: $uriSigner,
        );

        $response = $authenticator->start($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/contao/login', $response->getTargetUrl());
    }

    /**
     * @dataProvider getAuthenticationData
     */
    public function testStartsTheAuthenticationProcessOnCurrentPage(TokenInterface|null $token, ResponseException|null $exception): void
    {
        $pageModel = $this->createMock(PageModel::class);

        $request = new Request();
        $request->attributes->set('pageModel', $pageModel);

        $route = $this->createMock(PageRoute::class);
        $route
            ->expects($this->once())
            ->method('getDefaults')
            ->willReturn([])
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('getRoute')
            ->willReturn($route)
        ;

        $httpKernel = $this->createMock(HttpKernelInterface::class);

        if (!$exception instanceof ResponseException) {
            $httpKernel
                ->expects($this->once())
                ->method('handle')
                ->willReturn($this->createMock(RedirectResponse::class))
            ;
        } else {
            $httpKernel
                ->expects($this->once())
                ->method('handle')
                ->willThrowException($exception)
            ;
        }

        $authenticator = $this->getContaoLoginAuthenticator(
            userProvider: $this->createMock(ContaoUserProvider::class),
            errorPage: $pageModel,
            pageRegistry: $pageRegistry,
            httpKernel: $httpKernel,
            options: ['enable_csrf' => true],
        );

        $authenticator->start($request);
    }

    public function getAuthenticationData(): iterable
    {
        $token = $this->createMock(UsernamePasswordToken::class);

        $responseException = $this->createMock(ResponseException::class);
        $responseException
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->createMock(RedirectResponse::class))
        ;

        yield [$token, null];
        yield [$token, $responseException];
    }

    public function testThrowsExceptionIfThereIsNoErrorPage(): void
    {
        $this->expectException(UnauthorizedHttpException::class);

        $route = $this->createMock(PageRoute::class);
        $route
            ->expects($this->never())
            ->method('getDefaults')
            ->willReturn([])
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->never())
            ->method('getRoute')
            ->willReturn($route)
        ;

        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $httpKernel
            ->expects($this->never())
            ->method('handle')
            ->willReturn($this->createMock(RedirectResponse::class))
        ;

        $authenticator = $this->getContaoLoginAuthenticator(
            userProvider: $this->createMock(ContaoUserProvider::class),
            pageRegistry: $pageRegistry,
            httpKernel: $httpKernel,
            options: ['enable_csrf' => true],
        );

        $authenticator->start(new Request());
    }

    /**
     * @param UserProviderInterface<UserInterface>|null $userProvider
     */
    private function getContaoLoginAuthenticator(UserProviderInterface|null $userProvider = null, AuthenticationSuccessHandlerInterface|null $successHandler = null, AuthenticationFailureHandlerInterface|null $failureHandler = null, ScopeMatcher|null $scopeMatcher = null, RouterInterface|null $router = null, UriSigner|null $uriSigner = null, PageModel|null $errorPage = null, TokenStorageInterface|null $tokenStorage = null, PageRegistry|null $pageRegistry = null, HttpKernelInterface|null $httpKernel = null, RequestStack|null $requestStack = null, TwoFactorAuthenticator|null $twoFactorAuthenticator = null, array $options = []): ContaoLoginAuthenticator
    {
        $pageFinder = $this->createMock(PageFinder::class);
        $pageFinder
            ->expects($errorPage ? $this->once() : $this->any())
            ->method('findFirstPageOfTypeForRequest')
            ->with($this->isInstanceOf(Request::class), 'error_401')
            ->willReturn($errorPage)
        ;

        return new ContaoLoginAuthenticator(
            $userProvider ?? $this->mockUserProvider(),
            $successHandler ?? $this->mockSuccessHandler(),
            $failureHandler ?? $this->mockFailureHandler(),
            $scopeMatcher ?? $this->mockScopeMatcher(),
            $router ?? $this->mockRouter(),
            $uriSigner ?? $this->mockUriSigner(),
            $pageFinder,
            $tokenStorage ?? $this->mockTokenStorage(FrontendUser::class),
            $pageRegistry ?? $this->mockPageRegistry(),
            $httpKernel ?? $this->mockHttpKernel(),
            $requestStack ?? $this->mockRequestStack(),
            $twoFactorAuthenticator ?? $this->mockTwoFactorAuthenticator(),
            $options,
        );
    }

    /**
     * @return UserProviderInterface<UserInterface>
     */
    private function mockUserProvider(): UserProviderInterface
    {
        return $this->createMock(UserProviderInterface::class);
    }

    private function mockSuccessHandler(): AuthenticationSuccessHandlerInterface
    {
        return $this->createMock(AuthenticationSuccessHandlerInterface::class);
    }

    private function mockFailureHandler(): AuthenticationFailureHandlerInterface
    {
        return $this->createMock(AuthenticationFailureHandlerInterface::class);
    }

    private function mockRouter(): RouterInterface
    {
        return $this->createMock(RouterInterface::class);
    }

    private function mockUriSigner(): UriSigner
    {
        return $this->createMock(UriSigner::class);
    }

    private function mockPageRegistry(): PageRegistry
    {
        return $this->createMock(PageRegistry::class);
    }

    private function mockHttpKernel(): HttpKernelInterface
    {
        return $this->createMock(HttpKernelInterface::class);
    }

    private function mockRequestStack(): RequestStack
    {
        return $this->createMock(RequestStack::class);
    }

    private function mockTwoFactorAuthenticator(): TwoFactorAuthenticator
    {
        return $this->createMock(TwoFactorAuthenticator::class);
    }
}
