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

use Contao\CoreBundle\Security\Authentication\AuthenticationFailureHandler;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Translation\TranslatorInterface;

class AuthenticationFailureHandlerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $handler = $this->mockFailureHandler();

        $this->assertInstanceOf('Contao\CoreBundle\Security\Authentication\AuthenticationFailureHandler', $handler);
    }

    public function testRedirectsToTheRefererUponFrontendAuthenticationFailure(): void
    {
        $translator = $this->mockTranslator(
            'ERR.invalidLogin',
            'Login failed (note that usernames and passwords are case-sensitive)!'
        );

        $request = $this->mockRequest(['_scope' => 'frontend'], ['referer' => '/']);
        $request->setSession($this->mockSession());

        $utils = $this->createMock(HttpUtils::class);

        $utils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, '/')
            ->willReturn(new RedirectResponse('/'))
        ;

        $handler = $this->mockFailureHandler($translator, $utils);
        $response = $handler->onAuthenticationFailure($request, new AuthenticationException());

        /** @var Session $session */
        $session = $request->getSession();

        $this->assertNotNull($session);

        $error = $session->get('_security.last_error');

        $this->assertTrue($response->headers->contains('location', '/'));
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertInstanceOf('Symfony\Component\Security\Core\Exception\AuthenticationException', $error);

        $flashBag = $session->getFlashBag();

        $this->assertSame(
            'Login failed (note that usernames and passwords are case-sensitive)!',
            $flashBag->get('contao.FE.error')[0]
        );
    }

    public function testRedirectsToTheRequestUriUponBackendAuthenticationFailure(): void
    {
        $translator = $this->mockTranslator(
            'ERR.invalidLogin',
            'Login failed (note that usernames and passwords are case-sensitive)!'
        );

        $request = $this->mockRequest(['_scope' => 'backend']);
        $request->setSession($this->mockSession());

        $utils = $this->createMock(HttpUtils::class);

        $utils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, '/contao/login')
            ->willReturn(new RedirectResponse('/contao/login'))
        ;

        $handler = $this->mockFailureHandler($translator, $utils);
        $response = $handler->onAuthenticationFailure($request, new AuthenticationException());

        /** @var Session $session */
        $session = $request->getSession();

        $this->assertNotNull($session);

        $error = $session->get('_security.last_error');

        $this->assertTrue($response->headers->contains('location', '/contao/login'));
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertInstanceOf('Symfony\Component\Security\Core\Exception\AuthenticationException', $error);

        $flashBag = $session->getFlashBag();

        $this->assertSame(
            'Login failed (note that usernames and passwords are case-sensitive)!',
            $flashBag->get('contao.BE.error')[0]
        );
    }

    /**
     * Mocks a request with options, attributes and query parameters.
     *
     * @param array $attributes
     * @param array $headers
     *
     * @return Request
     */
    private function mockRequest(array $attributes = [], array $headers = []): Request
    {
        $request = Request::create('https://www.contao.org/contao/login');

        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        foreach ($attributes as $key => $value) {
            $request->attributes->set($key, $value);
        }

        return $request;
    }

    /**
     * Mocks a translator.
     *
     * @param string $key
     * @param string $translated
     *
     * @return TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockTranslator(string $key = '', string $translated = ''): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);

        $translator
            ->expects($this->once())
            ->method('trans')
            ->with($key)
            ->willReturn($translated)
        ;

        return $translator;
    }

    /**
     * Mocks an authentication failure handler.
     *
     * @param TranslatorInterface|null $translator
     * @param HttpUtils|null           $utils
     *
     * @return AuthenticationFailureHandler
     */
    private function mockFailureHandler(TranslatorInterface $translator = null, HttpUtils $utils = null): AuthenticationFailureHandler
    {
        $kernel = $this->createMock(HttpKernel::class);

        if (null === $utils) {
            $utils = $this->createMock(HttpUtils::class);
        }

        $scopeMatcher = $this->mockScopeMatcher();

        if (null === $translator) {
            $translator = $this->createMock(TranslatorInterface::class);
        }

        return new AuthenticationFailureHandler($kernel, $utils, $scopeMatcher, $translator);
    }
}
