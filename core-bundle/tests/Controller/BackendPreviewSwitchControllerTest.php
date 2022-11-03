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
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Contao\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class BackendPreviewSwitchControllerTest extends TestCase
{
    public function testExitsOnNonAjaxRequest(): void
    {
        $controller = new BackendPreviewSwitchController(
            $this->mockFrontendPreviewAuthenticator(),
            $this->mockTokenChecker(),
            $this->createMock(Connection::class),
            $this->mockSecurity(),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            $this->mockTranslator(),
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
            $this->createMock(Connection::class),
            $this->mockSecurity(),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            $this->mockTranslator(),
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

    public function testAddsShareLinkToToolbar(): void
    {
        $controller = new BackendPreviewSwitchController(
            $this->mockFrontendPreviewAuthenticator(),
            $this->mockTokenChecker(),
            $this->createMock(Connection::class),
            $this->mockSecurity(true),
            $this->getTwigMock(),
            $this->mockRouter(true),
            $this->mockTokenManager(),
            $this->mockTranslator(),
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

        $controller($request);
    }

    /**
     * @dataProvider getAuthenticationScenarios
     */
    public function testProcessesAuthentication(string|null $username, string $authenticateMethod): void
    {
        $frontendPreviewAuthenticator = $this->createMock(FrontendPreviewAuthenticator::class);
        $frontendPreviewAuthenticator
            ->expects($this->once())
            ->method($authenticateMethod)
            ->willReturn(true)
        ;

        $controller = new BackendPreviewSwitchController(
            $frontendPreviewAuthenticator,
            $this->mockTokenChecker($username),
            $this->createMock(Connection::class),
            $this->mockSecurity(),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            $this->mockTranslator(),
        );

        $request = new Request(
            [],
            [
                'FORM_SUBMIT' => 'tl_switch',
                'user' => $username,
            ],
            [],
            [],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', 'REQUEST_METHOD' => 'POST']
        );

        $response = $controller($request);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function getAuthenticationScenarios(): \Generator
    {
        yield [null, 'authenticateFrontendGuest'];
        yield ['', 'authenticateFrontendGuest'];
        yield ['k.jones', 'authenticateFrontendUser'];
    }

    public function testReturnsErrorWithInvalidUsername(): void
    {
        $controller = new BackendPreviewSwitchController(
            $this->mockFrontendPreviewAuthenticator(),
            $this->mockTokenChecker(),
            $this->createMock(Connection::class),
            $this->mockSecurity(),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            $this->mockTranslator(),
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
        $resultStatement = $this->createMock(Result::class);
        $resultStatement
            ->method('fetchFirstColumn')
            ->willReturn([])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('executeQuery')
            ->willReturn($resultStatement)
        ;

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform())
        ;

        $controller = new BackendPreviewSwitchController(
            $this->mockFrontendPreviewAuthenticator(),
            $this->mockTokenChecker(),
            $connection,
            $this->mockSecurity(),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            $this->mockTranslator(),
        );

        $request = new Request(
            [],
            ['FORM_SUBMIT' => 'datalist_members', 'value' => 'mem'],
            [],
            [],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', 'REQUEST_METHOD' => 'POST']
        );

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
            $this->createMock(Connection::class),
            $this->mockSecurity(false, null, []),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            $this->mockTranslator(),
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
            $this->createMock(Connection::class),
            $this->mockSecurity(false, FrontendUser::class, ['IS_AUTHENTICATED_FULLY', 'ROLE_MEMBER']),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            $this->mockTranslator(),
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
    private function mockRouter(bool $canShare = false, bool $isPreviewMode = true): RouterInterface
    {
        $router = $this->createMock(RouterInterface::class);

        if ($canShare) {
            $router
                ->expects($this->exactly(2))
                ->method('generate')
                ->withConsecutive(
                    [
                        'contao_backend',
                        ['do' => 'preview_link', 'act' => 'create', 'showUnpublished' => $isPreviewMode ? '1' : '', 'rt' => 'csrf', 'nb' => '1'],
                    ],
                    ['contao_backend_switch']
                )
                ->willReturn('/_contao/preview/1', '/contao/preview_switch')
            ;
        } else {
            $router
                ->method('generate')
                ->with('contao_backend_switch')
                ->willReturn('/contao/preview_switch')
            ;
        }

        return $router;
    }

    /**
     * @return TokenChecker&MockObject
     */
    private function mockTokenChecker(string $frontendUsername = null, bool $previewMode = true): TokenChecker
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
     * @param class-string<User> $userClass
     *
     * @return Security&MockObject
     */
    private function mockSecurity(bool $canShare = false, string|null $userClass = BackendUser::class, array $roles = ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH_MEMBER', 'IS_AUTHENTICATED_FULLY']): Security
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

        if ($canShare) {
            $security
                ->expects($this->exactly(2))
                ->method('isGranted')
                ->withConsecutive(
                    ['ROLE_ALLOWED_TO_SWITCH_MEMBER'],
                    [ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'preview_link']
                )
                ->willReturn(true, $canShare)
            ;
        } else {
            $security
                ->method('isGranted')
                ->willReturnCallback(static fn (string $role): bool => \in_array($role, $roles, true))
            ;
        }

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
     * @return ContaoCsrfTokenManager&MockObject
     */
    private function mockTokenManager(): ContaoCsrfTokenManager
    {
        $tokenManager = $this->createMock(ContaoCsrfTokenManager::class);
        $tokenManager
            ->method('getDefaultTokenValue')
            ->willReturn('csrf')
        ;

        return $tokenManager;
    }

    /**
     * @return TranslatorInterface&MockObject
     */
    private function mockTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(static fn (string $translation): string => $translation)
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
            ->willReturnCallback(static fn (string $user): bool => 'member' === $user)
        ;

        $authenticator
            ->method('authenticateFrontendGuest')
            ->willReturn(true)
        ;

        return $authenticator;
    }
}
