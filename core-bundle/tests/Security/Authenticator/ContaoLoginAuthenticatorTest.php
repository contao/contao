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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\Authenticator\ContaoLoginAuthenticator;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Contao\PageModel;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\Passport\Credentials\TwoFactorCodeCredentials;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\TwoFactorAuthenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

class ContaoLoginAuthenticatorTest extends TestCase
{
    /**
     * @dataProvider getRequestSupportsData
     */
    public function testSupportsTheRequest(bool $expected, Request $request): void
    {
        $authenticator = $this->mockContaoLoginAuthenticator();

        $this->assertSame($expected, $authenticator->supports($request));
    }

    public function getRequestSupportsData(): \Generator
    {
        $request1 = new Request();
        $request1->setMethod('POST');

        $request2 = new Request();
        $request2->setMethod('POST');
        $request2->request->set('FORM_SUBMIT', 'foobar');

        $request3 = new Request();
        $request3->setMethod('POST');
        $request3->request->set('FORM_SUBMIT', 'tl_login');

        $request4 = new Request();
        $request4->setMethod('POST');
        $request4->request->set('FORM_SUBMIT', 'tl_login_1');

        yield [false, new Request()];
        yield [false, $request1];
        yield [false, $request2];
        yield [true, $request3];
        yield [true, $request4];
    }

    /**
     * @dataProvider getRequestInteractiveData
     */
    public function testIfAuthenticationIsInteractive(bool $expected, RequestStack|null $requestStack): void
    {
        $authenticator = $this->mockContaoLoginAuthenticator(
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $requestStack,
        );

        $this->assertSame($expected, $authenticator->isInteractive());
    }

    public function getRequestInteractiveData(): \Generator
    {
        $requestStack1 = new RequestStack();
        $requestStack1->push(new Request());

        $request = new Request();
        $request->attributes->set('pageModel', $this->createMock(PageModel::class));

        $requestStack2 = new RequestStack();
        $requestStack2->push($request);

        yield [false, null];
        yield [false, $requestStack1];
        yield [true, $requestStack2];
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
            ->willReturn('url')
        ;

        $uriSigner = $this->createMock(UriSigner::class);
        $uriSigner
            ->expects($this->once())
            ->method('sign')
            ->willReturn('url')
        ;

        $authenticator = $this->mockContaoLoginAuthenticator(
            null,
            null,
            null,
            $scopeMatcher,
            $router,
            $uriSigner
        );

        $response = $authenticator->start(new Request());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('url', $response->getTargetUrl());
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

        $authenticator = $this->mockContaoLoginAuthenticator(
            null,
            $successHandler,
            $failureHandler,
        );

        $authenticator->onAuthenticationFailure($request, $exception);
        $authenticator->onAuthenticationSuccess($request, $token, 'firewall');
    }

    /**
     * @dataProvider getTokenData
     */
    public function testTokenCreation(string $expected, Passport $passport): void
    {
        $authenticator = $this->mockContaoLoginAuthenticator();

        $this->assertInstanceOf($expected, $authenticator->createToken($passport, 'firewall'));
    }

    public function getTokenData(): \Generator
    {
        $badge1 = $this->createMock(TwoFactorCodeCredentials::class);
        $badge1
            ->expects($this->once())
            ->method('getTwoFactorToken')
            ->willReturn($this->createMock(TwoFactorToken::class))
        ;

        $passport1 = $this->createMock(Passport::class);
        $passport1
            ->expects($this->once())
            ->method('getBadge')
            ->willReturn($badge1)
        ;

        $twoFactorToken1 = $this->createMock(TwoFactorToken::class);
        $twoFactorToken1
            ->expects($this->once())
            ->method('allTwoFactorProvidersAuthenticated')
            ->willReturn(true)
        ;

        $twoFactorToken1
            ->expects($this->once())
            ->method('getAuthenticatedToken')
            ->willReturn($this->createMock(PostAuthenticationToken::class))
        ;

        $badge2 = $this->createMock(TwoFactorCodeCredentials::class);
        $badge2
            ->expects($this->once())
            ->method('getTwoFactorToken')
            ->willReturn($twoFactorToken1)
        ;

        $passport2 = $this->createMock(Passport::class);
        $passport2
            ->expects($this->once())
            ->method('getBadge')
            ->willReturn($badge2)
        ;

        yield [PostAuthenticationToken::class, $this->createMock(Passport::class)];
        yield [TwoFactorToken::class, $passport1];
        yield [PostAuthenticationToken::class, $passport2];
    }

    private function mockContaoLoginAuthenticator(UserProviderInterface $userProvider = null, AuthenticationSuccessHandlerInterface $successHandler = null, AuthenticationFailureHandlerInterface $failureHandler = null, ScopeMatcher $scopeMatcher = null, RouterInterface $router = null, UriSigner $uriSigner = null, ContaoFramework $framework = null, TokenStorageInterface $tokenStorage = null, PageRegistry $pageRegistry = null, HttpKernelInterface $httpKernel = null, RequestStack $requestStack = null, TwoFactorAuthenticator $twoFactorAuthenticator = null, array $options = []): ContaoLoginAuthenticator
    {
        return new ContaoLoginAuthenticator(
            $userProvider ?? $this->mockUserProvider(),
            $successHandler ?? $this->mockSuccessHandler(),
            $failureHandler ?? $this->mockFailureHandler(),
            $scopeMatcher ?? $this->mockScopeMatcher(),
            $router ?? $this->mockRouter(),
            $uriSigner ?? $this->mockUriSigner(),
            $framework ?? $this->mockContaoFramework(),
            $tokenStorage ?? $this->mockTokenStorage(FrontendUser::class),
            $pageRegistry ?? $this->mockPageRegistry(),
            $httpKernel ?? $this->mockHttpKernel(),
            $requestStack ?? $this->mockRequestStack(),
            $twoFactorAuthenticator ?? $this->mockTwoFactorAuthenticator(),
            $options,
        );
    }

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
