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
use Scheb\TwoFactorBundle\Security\Http\Authenticator\TwoFactorAuthenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class ContaoLoginAuthenticatorTest extends TestCase
{
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
