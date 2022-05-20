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
use Contao\FrontendUser;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ForwardCompatibility\DriverStatement;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class BackendPreviewSwitchControllerTest extends TestCase
{
    public function testExitsOnNonAjaxRequest(): void
    {
        $controller = new BackendPreviewSwitchController(
            $this->mockFrontendPreviewAuthenticator(),
            $this->mockTokenChecker(),
            $this->mockConnection(),
            $this->mockSecurity(),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            $this->mockTranslator(),
            'csrf'
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
            $this->mockFrontendPreviewAuthenticator(),
            $this->mockTokenChecker(),
            $this->mockConnection(),
            $this->mockSecurity(),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            $this->mockTranslator(),
            'csrf'
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

    public function testProcessesGuestAuthentication(): void
    {
        $controller = new BackendPreviewSwitchController(
            $this->mockFrontendPreviewAuthenticator(),
            $this->mockTokenChecker(),
            $this->mockConnection(),
            $this->mockSecurity(),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            $this->mockTranslator(),
            'csrf'
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

    public function testProcessesUserAuthentication(): void
    {
        $controller = new BackendPreviewSwitchController(
            $this->mockFrontendPreviewAuthenticator(),
            $this->mockTokenChecker(),
            $this->mockConnection(),
            $this->mockSecurity(),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            $this->mockTranslator(),
            'csrf'
        );

        $request = $this->createMock(Request::class);
        $request->request = new ParameterBag(['FORM_SUBMIT' => 'tl_switch', 'user' => 'member']);

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

    public function testReturnsErrorWithInvalidUsername(): void
    {
        $controller = new BackendPreviewSwitchController(
            $this->mockFrontendPreviewAuthenticator(),
            $this->mockTokenChecker(),
            $this->mockConnection(),
            $this->mockSecurity(),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            $this->mockTranslator(),
            'csrf'
        );

        $request = $this->createMock(Request::class);
        $request->request = new ParameterBag(['FORM_SUBMIT' => 'tl_switch', 'user' => 'foobar']);

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

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertSame('ERR.previewSwitchInvalidUsername', $response->getContent());
    }

    public function testReturnsEmptyMemberList(): void
    {
        $controller = new BackendPreviewSwitchController(
            $this->mockFrontendPreviewAuthenticator(),
            $this->mockTokenChecker(),
            $this->mockConnection([]),
            $this->mockSecurity(),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            $this->mockTranslator(),
            'csrf'
        );

        $request = $this->createMock(Request::class);
        $request->request = new ParameterBag(['FORM_SUBMIT' => 'datalist_members', 'value' => 'mem']);

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

    public function testExitsAsUnauthenticatedUser(): void
    {
        $controller = new BackendPreviewSwitchController(
            $this->mockFrontendPreviewAuthenticator(),
            $this->mockTokenChecker(),
            $this->mockConnection(),
            $this->mockSecurity(null, []),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            $this->mockTranslator(),
            'csrf'
        );

        $request = $this->createMock(Request::class);
        $request
            ->method('isXmlHttpRequest')
            ->willReturn(true)
        ;

        $response = $controller($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testExitsAsUnauthorizedUser(): void
    {
        $controller = new BackendPreviewSwitchController(
            $this->mockFrontendPreviewAuthenticator(),
            $this->mockTokenChecker(),
            $this->mockConnection(),
            $this->mockSecurity(FrontendUser::class, ['IS_AUTHENTICATED_FULLY', 'ROLE_MEMBER']),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            $this->mockTranslator(),
            'csrf'
        );

        $request = $this->createMock(Request::class);
        $request
            ->method('isXmlHttpRequest')
            ->willReturn(true)
        ;

        $response = $controller($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
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
    private function mockSecurity(?string $userClass = BackendUser::class, array $roles = ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH_MEMBER', 'IS_AUTHENTICATED_FULLY']): Security
    {
        $user = null;

        if (null !== $userClass) {
            $user = $this->createMock($userClass);
        }

        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn($user)
        ;

        $security
            ->method('isGranted')
            ->willReturnCallback(
                static function (string $role) use ($roles): bool {
                    return \in_array($role, $roles, true);
                }
            )
        ;

        return $security;
    }

    /**
     * @return Environment&MockObject
     */
    private function getTwigMock(string $render = 'CONTAO'): Environment
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->method('render')
            ->willReturn($render)
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

    /**
     * @return Connection&MockObject
     */
    private function mockConnection(array $return = []): Connection
    {
        $resultStatement = $this->createMock(DriverStatement::class);
        $resultStatement
            ->method('fetchFirstColumn')
            ->willReturn($return)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('executeQuery')
            ->willReturn($resultStatement)
        ;

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform())
        ;

        return $connection;
    }

    /**
     * @return TranslatorInterface&MockObject
     */
    private function mockTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(
                static function (string $translation): string {
                    return $translation;
                }
            )
        ;

        return $translator;
    }

    /**
     * @return FrontendPreviewAuthenticator&MockObject
     */
    private function mockFrontendPreviewAuthenticator(): FrontendPreviewAuthenticator
    {
        $authenticator = $this->createMock(FrontendPreviewAuthenticator::class);
        $authenticator
            ->method('authenticateFrontendUser')
            ->willReturnCallback(
                static function (string $user): bool {
                    return 'member' === $user;
                }
            )
        ;

        $authenticator
            ->method('authenticateFrontendGuest')
            ->willReturn(true)
        ;

        return $authenticator;
    }
}
