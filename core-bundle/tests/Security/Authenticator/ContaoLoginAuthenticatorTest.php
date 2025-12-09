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
use PHPUnit\Framework\Attributes\DataProvider;
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
use Twig\Environment;

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

        $requestStack = new RequestStack([new Request()]);

        $authenticator = $this->getContaoLoginAuthenticator(requestStack: $requestStack);

        $this->assertFalse($authenticator->isInteractive());

        $request = new Request();
        $request->attributes->set('pageModel', $this->createStub(PageModel::class));

        $requestStack = new RequestStack([$request]);

        $authenticator = $this->getContaoLoginAuthenticator(requestStack: $requestStack);

        $this->assertTrue($authenticator->isInteractive());
    }

    public function testCallsTheSuccessAndFailureHandlers(): void
    {
        $request = new Request();
        $exception = new AuthenticationException();
        $token = $this->createStub(TokenInterface::class);

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
            $authenticator->createToken($this->createStub(Passport::class), 'firewall'),
        );

        $badge = $this->createMock(TwoFactorCodeCredentials::class);
        $badge
            ->expects($this->once())
            ->method('getTwoFactorToken')
            ->willReturn($this->createStub(TwoFactorToken::class))
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
            ->willReturn($this->createStub(PostAuthenticationToken::class))
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
            ->willReturn($this->createStub(TwoFactorTokenInterface::class))
        ;

        $twoFactorAuthenticator = $this->createMock(TwoFactorAuthenticator::class);
        $twoFactorAuthenticator
            ->expects($this->once())
            ->method('authenticate')
            ->with($request)
            ->willReturn($this->createStub(Passport::class))
        ;

        $authenticator = $this->getContaoLoginAuthenticator(
            tokenStorage: $tokenStorage,
            twoFactorAuthenticator: $twoFactorAuthenticator,
        );

        $authenticator->authenticate($request);
    }

    #[DataProvider('getUserData')]
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

        $token = $this->createStub(UsernamePasswordToken::class);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $authenticator = $this->getContaoLoginAuthenticator(
            userProvider: $this->createStub(ContaoUserProvider::class),
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

    public function testTriggersClientSideReloadOnATurboStreamRequest(): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(true)
        ;

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with('@Contao/backend/reload.stream.html.twig')
            ->willReturn('<stream content>')
        ;

        $authenticator = $this->getContaoLoginAuthenticator(
            scopeMatcher: $scopeMatcher,
            twig: $twig,
        );

        $request = Request::create('https://example.com/foo/bar');
        $request->headers->set('Accept', 'text/vnd.turbo-stream.html');

        $response = $authenticator->start($request);

        $this->assertSame('<stream content>', $response->getContent());
    }

    public function testStartsTheAuthenticationProcessOnCurrentPageWithoutException(): void
    {
        $this->assertStartsTheAuthenticationProcessOnCurrentPage(null);
    }

    public function testStartsTheAuthenticationProcessOnCurrentPageWithException(): void
    {
        $responseException = $this->createMock(ResponseException::class);
        $responseException
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->createStub(RedirectResponse::class))
        ;

        $this->assertStartsTheAuthenticationProcessOnCurrentPage($responseException);
    }

    public function assertStartsTheAuthenticationProcessOnCurrentPage(ResponseException|null $exception): void
    {
        $pageModel = $this->createStub(PageModel::class);

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
                ->willReturn($this->createStub(RedirectResponse::class))
            ;
        } else {
            $httpKernel
                ->expects($this->once())
                ->method('handle')
                ->willThrowException($exception)
            ;
        }

        $authenticator = $this->getContaoLoginAuthenticator(
            userProvider: $this->createStub(ContaoUserProvider::class),
            errorPage: $pageModel,
            pageRegistry: $pageRegistry,
            httpKernel: $httpKernel,
            options: ['enable_csrf' => true],
        );

        $authenticator->start($request);
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
            ->willReturn($this->createStub(RedirectResponse::class))
        ;

        $authenticator = $this->getContaoLoginAuthenticator(
            userProvider: $this->createStub(ContaoUserProvider::class),
            pageRegistry: $pageRegistry,
            httpKernel: $httpKernel,
            options: ['enable_csrf' => true],
        );

        $authenticator->start(new Request());
    }

    /**
     * @param UserProviderInterface<UserInterface>|null $userProvider
     */
    private function getContaoLoginAuthenticator(UserProviderInterface|null $userProvider = null, AuthenticationSuccessHandlerInterface|null $successHandler = null, AuthenticationFailureHandlerInterface|null $failureHandler = null, ScopeMatcher|null $scopeMatcher = null, RouterInterface|null $router = null, UriSigner|null $uriSigner = null, PageModel|null $errorPage = null, TokenStorageInterface|null $tokenStorage = null, PageRegistry|null $pageRegistry = null, HttpKernelInterface|null $httpKernel = null, RequestStack|null $requestStack = null, TwoFactorAuthenticator|null $twoFactorAuthenticator = null, array $options = [], Environment|null $twig = null): ContaoLoginAuthenticator
    {
        if ($errorPage) {
            $pageFinder = $this->createMock(PageFinder::class);
            $pageFinder
                ->expects($this->once())
                ->method('findFirstPageOfTypeForRequest')
                ->with($this->isInstanceOf(Request::class), 'error_401')
                ->willReturn($errorPage)
            ;
        } else {
            $pageFinder = $this->createStub(PageFinder::class);
        }

        return new ContaoLoginAuthenticator(
            $userProvider ?? $this->createStub(UserProviderInterface::class),
            $successHandler ?? $this->createStub(AuthenticationSuccessHandlerInterface::class),
            $failureHandler ?? $this->createStub(AuthenticationFailureHandlerInterface::class),
            $scopeMatcher ?? $this->mockScopeMatcher(),
            $router ?? $this->createStub(RouterInterface::class),
            $uriSigner ?? $this->createStub(UriSigner::class),
            $pageFinder,
            $tokenStorage ?? $this->mockTokenStorage(FrontendUser::class),
            $pageRegistry ?? $this->createStub(PageRegistry::class),
            $httpKernel ?? $this->createStub(HttpKernelInterface::class),
            $requestStack ?? $this->createStub(RequestStack::class),
            $twoFactorAuthenticator ?? $this->createStub(TwoFactorAuthenticator::class),
            $options,
            $twig ?? $this->createStub(Environment::class),
        );
    }
}
