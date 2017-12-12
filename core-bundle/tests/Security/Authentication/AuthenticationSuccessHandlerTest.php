<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\HttpUtils;

class AuthenticationSuccessHandlerTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        unset($GLOBALS['TL_HOOKS']);
    }

    public function testCanBeInstantiated(): void
    {
        $handler = $this->mockSuccessHandler();

        $this->assertInstanceOf('Contao\CoreBundle\Security\Authentication\AuthenticationSuccessHandler', $handler);
    }

    public function testRedirectsToAGivenTargetReferer(): void
    {
        $request = $this->mockRequest(['_target_referer' => 'foobar']);
        $token = $this->createMock(TokenInterface::class);

        $handler = $this->mockSuccessHandler();
        $response = $handler->onAuthenticationSuccess($request, $token);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertTrue($response->headers->contains('location', 'foobar'));
    }

    public function testRedirectsToTheDefaultTargetPathIfThereIsNoValidUser(): void
    {
        $request = $this->mockRequest();
        $token = $this->createMock(TokenInterface::class);

        $handler = $this->mockSuccessHandler(['default_target_path' => 'foobar']);
        $response = $handler->onAuthenticationSuccess($request, $token);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertTrue($response->headers->contains('location', 'foobar'));
    }

    public function testRedirectsBackendUsersUponLogin(): void
    {
        $request = $this->mockRequest([], ['_route' => 'contao_backend_login']);
        $utils = $this->createMock(HttpUtils::class);

        $utils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, 'foobar')
            ->willReturn(new RedirectResponse('foobar'))
        ;

        $token = $this->mockToken(BackendUser::class);

        $handler = $this->mockSuccessHandler(['default_target_path' => 'foobar'], $utils);
        $response = $handler->onAuthenticationSuccess($request, $token);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertTrue($response->headers->contains('location', 'foobar'));
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using the postAuthenticate hook has been deprecated %s.
     */
    public function testExecutesThePostAuthenticateHookWithABackendUser(): void
    {
        $GLOBALS['TL_HOOKS'] = [
            'postAuthenticate' => [[\get_class($this), 'executePostAuthenticateHookWithABackendUser']],
        ];

        $request = $this->mockRequest([], ['_route' => 'contao_backend_login']);
        $utils = $this->createMock(HttpUtils::class);

        $utils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, 'foobar')
            ->willReturn(new RedirectResponse('foobar'))
        ;

        $token = $this->mockToken(BackendUser::class);
        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->willReturn($this)
        ;

        $handler = $this->mockSuccessHandler(['default_target_path' => 'foobar'], $utils, $framework);
        $response = $handler->onAuthenticationSuccess($request, $token);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertTrue($response->headers->contains('location', 'foobar'));
    }

    /**
     * @param BackendUser $user
     */
    public static function executePostAuthenticateHookWithABackendUser(BackendUser $user): void
    {
        self::assertInstanceOf('Contao\BackendUser', $user);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using the postAuthenticate hook has been deprecated %s.
     */
    public function testExecutesThePostAuthenticateHookWithAFrontendUser(): void
    {
        $GLOBALS['TL_HOOKS'] = [
            'postAuthenticate' => [[\get_class($this), 'executePostAuthenticateHookWithAFrontendUser']],
        ];

        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->willReturn($this)
        ;

        $request = $this->mockRequest([], ['_route' => 'contao_backend_login']);
        $token = $this->mockToken(FrontendUser::class);

        $handler = $this->mockSuccessHandler(['default_target_path' => 'foobar'], null, $framework);
        $response = $handler->onAuthenticationSuccess($request, $token);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertTrue($response->headers->contains('location', 'foobar'));
    }

    /**
     * @param FrontendUser $user
     */
    public static function executePostAuthenticateHookWithAFrontendUser(FrontendUser $user): void
    {
        self::assertInstanceOf('Contao\FrontendUser', $user);
    }

    public function testRedirectsBackendUsersWithoutLastPageVisited(): void
    {
        $request = $this->mockRequest([], ['_route' => 'contao_root']);
        $token = $this->mockToken(BackendUser::class);
        $router = $this->mockRouter('contao_backend_login', [], '/contao');

        $handler = $this->mockSuccessHandler(['default_target_path' => 'foobar'], null, null, $router);
        $response = $handler->onAuthenticationSuccess($request, $token);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertTrue($response->headers->contains('location', '/contao'));
    }

    public function testRedirectsBackendUsersWithLastPageVisited(): void
    {
        $router = $this->mockRouter(
            'contao_backend_login',
            ['referer' => 'L2NvbnRhbz9kbz1mb29iYXI='],
            '/contao?do=foobar'
        );

        $request = $this->mockRequest([], ['_route' => 'contao_backend'], ['do' => 'foobar']);
        $request->server->set('REQUEST_URI', '/contao?do=foobar');

        $token = $this->mockToken(BackendUser::class);

        $handler = $this->mockSuccessHandler(['default_target_path' => 'foobar'], null, null, $router);
        $response = $handler->onAuthenticationSuccess($request, $token);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertTrue($response->headers->contains('location', '/contao?do=foobar'));
    }

    public function testRedirectsFrontendUsersWithoutGroups(): void
    {
        $request = $this->mockRequest();
        $token = $this->mockToken(FrontendUser::class);

        $handler = $this->mockSuccessHandler(['default_target_path' => 'foobar']);
        $response = $handler->onAuthenticationSuccess($request, $token);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertTrue($response->headers->contains('location', 'foobar'));
    }

    public function testRedirectsFrontendUsersWithGroups(): void
    {
        $page = $this->createMock(PageModel::class);

        $page
            ->expects($this->once())
            ->method('getAbsoluteUrl')
            ->willReturn('group-page')
        ;

        $adapter = $this->mockConfiguredAdapter(['findFirstActiveByMemberGroups' => $page]);
        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);
        $request = $this->mockRequest();
        $token = $this->mockToken(FrontendUser::class, [1, 2, 3]);

        $handler = $this->mockSuccessHandler(['default_target_path' => 'foobar'], null, $framework);
        $response = $handler->onAuthenticationSuccess($request, $token);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertTrue($response->headers->contains('location', 'group-page'));
    }

    public function testRedirectsFrontendUsersWithGroupsAndInvalidGroupPage(): void
    {
        $adapter = $this->mockConfiguredAdapter(['findFirstActiveByMemberGroups' => null]);
        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);
        $request = $this->mockRequest();
        $token = $this->mockToken(FrontendUser::class, [1, 2, 3]);

        $handler = $this->mockSuccessHandler(['default_target_path' => 'foobar'], null, $framework);
        $response = $handler->onAuthenticationSuccess($request, $token);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertTrue($response->headers->contains('location', 'foobar'));
    }

    /**
     * Mocks the request with options, attributes and query parameters.
     *
     * @param array $options
     * @param array $attributes
     * @param array $query
     *
     * @return Request
     */
    private function mockRequest(array $options = [], array $attributes = [], array $query = []): Request
    {
        $request = Request::create('https://www.contao.org');

        foreach ($options as $key => $value) {
            $request->request->set($key, $value);
        }

        foreach ($attributes as $key => $value) {
            $request->attributes->set($key, $value);
        }

        foreach ($query as $key => $value) {
            $request->query->set($key, $value);
        }

        return $request;
    }

    /**
     * Mocks a token with an optional user object.
     *
     * @param string     $class
     * @param array|null $groups
     *
     * @return TokenInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockToken($class = null, array $groups = null): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);

        if (null === $class) {
            return $token;
        }

        if (null !== $groups) {
            $user = $this->mockClassWithProperties($class, ['groups' => serialize($groups)]);
        } else {
            $user = $this->createMock($class);
        }

        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        return $token;
    }

    /**
     * Mocks a router with a route, parameters and a return value.
     *
     * @param string|null $route
     * @param array       $parameters
     * @param null        $return
     *
     * @return RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockRouter(string $route = null, array $parameters = [], $return = null): RouterInterface
    {
        $router = $this->createMock(RouterInterface::class);

        if (null !== $route) {
            $router
                ->expects($this->once())
                ->method('generate')
                ->with($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL)
                ->willReturn($return)
            ;
        }

        return $router;
    }

    /**
     * Mocks an authentication success handler.
     *
     * @param array                         $options
     * @param HttpUtils|null                $utils
     * @param ContaoFrameworkInterface|null $framework
     * @param RouterInterface|null          $router
     *
     * @return AuthenticationSuccessHandler
     */
    private function mockSuccessHandler(array $options = [], HttpUtils $utils = null, ContaoFrameworkInterface $framework = null, RouterInterface $router = null): AuthenticationSuccessHandler
    {
        if (null === $utils) {
            $utils = $this->createMock(HttpUtils::class);
        }

        if (null === $framework) {
            $framework = $this->mockContaoFramework();
        }

        if (null === $router) {
            $router = $this->createMock(RouterInterface::class);
        }

        return new AuthenticationSuccessHandler($utils, $framework, $router, $options);
    }
}
