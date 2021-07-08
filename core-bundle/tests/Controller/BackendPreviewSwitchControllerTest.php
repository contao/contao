<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller;

use Contao\BackendUser;
use Contao\CoreBundle\Controller\BackendPreviewSwitchController;
use Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

class BackendPreviewSwitchControllerTest extends TestCase
{
    public function testExitsOnNonAjaxRequest(): void
    {
        $controller = new BackendPreviewSwitchController(
            $this->createMock(FrontendPreviewAuthenticator::class),
            $this->mockTokenChecker(),
            $this->createMock(Connection::class),
            $this->mockSecurity(),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            'csrf',
            []
        );

        $request = $this->createMock(Request::class);
        $request
            ->method('isXmlHttpRequest')
            ->willReturn(false)
        ;

        $response = $controller($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testRendersToolbar(): void
    {
        $controller = new BackendPreviewSwitchController(
            $this->createMock(FrontendPreviewAuthenticator::class),
            $this->mockTokenChecker(),
            $this->createMock(Connection::class),
            $this->mockSecurity(),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            'csrf',
            []
        );

        $request = $this->createMock(Request::class);
        $request
            ->method('isXmlHttpRequest')
            ->willReturn(true)
        ;

        $request
            ->method('isMethod')
            ->with('GET')
            ->willReturn(true)
        ;

        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('CONTAO', $response->getContent());
    }

    public function testProcessesAuthentication(): void
    {
        $controller = new BackendPreviewSwitchController(
            $this->createMock(FrontendPreviewAuthenticator::class),
            $this->mockTokenChecker(),
            $this->createMock(Connection::class),
            $this->mockSecurity(),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            'csrf',
            []
        );

        $request = $this->createMock(Request::class);
        $request->request = new ParameterBag(['FORM_SUBMIT' => 'tl_switch']);

        $request
            ->method('isXmlHttpRequest')
            ->willReturn(true)
        ;

        $request
            ->method('isMethod')
            ->with('GET')
            ->willReturn(false)
        ;

        $response = $controller($request);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testReturnsEmptyMemberList(): void
    {
        $controller = new BackendPreviewSwitchController(
            $this->createMock(FrontendPreviewAuthenticator::class),
            $this->mockTokenChecker(),
            $this->createMock(Connection::class),
            $this->mockSecurity(),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            'csrf',
            []
        );

        $request = $this->createMock(Request::class);
        $request->request = new ParameterBag(['FORM_SUBMIT' => 'datalist_members']);

        $request
            ->method('isXmlHttpRequest')
            ->willReturn(true)
        ;

        $request
            ->method('isMethod')
            ->with('GET')
            ->willReturn(false)
        ;

        $response = $controller($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(json_encode([]), $response->getContent());
    }

    /**
     * @return RouterInterface&MockObject
     */
    private function mockRouter(): RouterInterface
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->method('generate')
            ->with('contao_backend_switch')
            ->willReturn('/contao/preview_switch')
        ;

        return $router;
    }

    /**
     * @return TokenChecker&MockObject
     */
    private function mockTokenChecker(?string $frontendUsername = null, bool $previewMode = true): TokenChecker
    {
        $tokenChecker = $this->createMock(TokenChecker::class);
        $tokenChecker
            ->method('getFrontendUsername')
            ->willReturn($frontendUsername)
        ;

        $tokenChecker
            ->method('isPreviewMode')
            ->willReturn($previewMode)
        ;

        return $tokenChecker;
    }

    /**
     * @return Security&MockObject
     */
    private function mockSecurity(): Security
    {
        $user = $this->createMock(BackendUser::class);

        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn($user)
        ;

        return $security;
    }

    /**
     * @return Environment&MockObject
     */
    private function getTwigMock(): Environment
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->method('render')
            ->willReturn('CONTAO')
        ;

        return $twig;
    }

    /**
     * @return CsrfTokenManagerInterface&MockObject
     */
    private function mockTokenManager(): CsrfTokenManagerInterface
    {
        $twig = $this->createMock(CsrfTokenManagerInterface::class);
        $twig
            ->method('getToken')
            ->willReturn(new CsrfToken('csrf', 'csrf'))
        ;

        return $twig;
    }
}
