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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

class BackendPreviewSwitchControllerTest extends TestCase
{
    public function testExitsOnNonAjaxRequest(): void
    {
        $controller = new BackendPreviewSwitchController(
            $this->mockFrontendPreviewAuthenticator(),
            $this->mockTokenChecker(),
            $this->createStub(Connection::class),
            $this->mockSecurity(),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            $this->mockTranslator(),
        );

        $request = $this->createStub(Request::class);
        $request
            ->method('isXmlHttpRequest')
            ->willReturn(false)
        ;

        $response = $controller($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    #[DataProvider('providePreviewToolbarTemplateScenarios')]
    public function testRendersToolbar(bool $legacyTemplateExists, string $expectedTemplate, array $backendAttributes = [], string $backendBadgeTitle = ''): void
    {
        $loader = $this->createStub(LoaderInterface::class);
        $loader
            ->method('exists')
            ->with('@ContaoCore/Frontend/preview_toolbar_base.html.twig')
            ->willReturn($legacyTemplateExists)
        ;

        $twig = $this->getTwigMock();
        $twig
            ->method('getLoader')
            ->willReturn($loader)
        ;

        $controller = new BackendPreviewSwitchController(
            $this->mockFrontendPreviewAuthenticator(),
            $this->mockTokenChecker(),
            $this->createStub(Connection::class),
            $this->mockSecurity(),
            $twig,
            $this->mockRouter(),
            $this->mockTokenManager(),
            $this->mockTranslator(),
            $backendAttributes,
            $backendBadgeTitle,
        );

        $request = $this->createStub(Request::class);
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
        $templateContent = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $expectedAttributes = array_merge(...array_map(static fn (string $k, string $v) => ['data-'.$k => $v], array_keys($backendAttributes), $backendAttributes));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame($expectedTemplate, $templateContent['name']);
        $this->assertSame($expectedAttributes, $templateContent['data']['attributes']);
        $this->assertSame($backendBadgeTitle, $templateContent['data']['badgeTitle']);
    }

    public static function providePreviewToolbarTemplateScenarios(): iterable
    {
        yield 'legacy template' => [true, '@ContaoCore/Frontend/preview_toolbar_base.html.twig'];

        yield 'modern template' => [false, '@Contao/frontend_preview/toolbar.html.twig'];

        yield 'back end attributes' => [false, '@Contao/frontend_preview/toolbar.html.twig', ['foo' => 'bar']];

        yield 'badge title' => [false, '@Contao/frontend_preview/toolbar.html.twig', [], 'Some badge title'];
    }

    public function testAddsShareLinkToToolbar(): void
    {
        $controller = new BackendPreviewSwitchController(
            $this->mockFrontendPreviewAuthenticator(),
            $this->mockTokenChecker(),
            $this->createStub(Connection::class),
            $this->mockSecurity(true),
            $this->getTwigMock(),
            $this->mockRouter(true),
            $this->mockTokenManager(),
            $this->mockTranslator(),
        );

        $request = $this->createStub(Request::class);
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

    #[DataProvider('getAuthenticationScenarios')]
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
            $this->createStub(Connection::class),
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
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', 'REQUEST_METHOD' => 'POST'],
        );

        $response = $controller($request);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public static function getAuthenticationScenarios(): iterable
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
            $this->createStub(Connection::class),
            $this->mockSecurity(),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            $this->mockTranslator(),
        );

        $request = $this->createStub(Request::class);
        $request->request = new InputBag(['FORM_SUBMIT' => 'tl_switch', 'user' => 'foobar']);

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
        $resultStatement = $this->createStub(Result::class);
        $resultStatement
            ->method('fetchFirstColumn')
            ->willReturn([])
        ;

        $connection = $this->createStub(Connection::class);
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
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', 'REQUEST_METHOD' => 'POST'],
        );

        $response = $controller($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(json_encode([], JSON_THROW_ON_ERROR), $response->getContent());
    }

    public function testExitsAsUnauthenticatedUser(): void
    {
        $controller = new BackendPreviewSwitchController(
            $this->mockFrontendPreviewAuthenticator(),
            $this->mockTokenChecker(),
            $this->createStub(Connection::class),
            $this->mockSecurity(false, null, []),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            $this->mockTranslator(),
        );

        $request = $this->createStub(Request::class);
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
            $this->createStub(Connection::class),
            $this->mockSecurity(false, FrontendUser::class, ['IS_AUTHENTICATED_FULLY', 'ROLE_MEMBER']),
            $this->getTwigMock(),
            $this->mockRouter(),
            $this->mockTokenManager(),
            $this->mockTranslator(),
        );

        $request = $this->createStub(Request::class);
        $request
            ->method('isXmlHttpRequest')
            ->willReturn(true)
        ;

        $response = $controller($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    private function mockRouter(bool $canShare = false): RouterInterface
    {
        if ($canShare) {
            $router = $this->createMock(RouterInterface::class);
            $router
                ->expects($this->exactly(2))
                ->method('generate')
                ->willReturnMap([
                    [
                        'contao_backend',
                        ['do' => 'preview_link', 'act' => 'create', 'showUnpublished' => true, 'rt' => 'csrf', 'nb' => '1'],
                        '/_contao/preview/1',
                    ],
                    ['contao_backend_switch', '/contao/preview_switch'],
                ])
            ;
        } else {
            $router = $this->createStub(RouterInterface::class);
            $router
                ->method('generate')
                ->with('contao_backend_switch')
                ->willReturn('/contao/preview_switch')
            ;
        }

        return $router;
    }

    private function mockTokenChecker(string|null $frontendUsername = null): TokenChecker&Stub
    {
        $tokenChecker = $this->createStub(TokenChecker::class);
        $tokenChecker
            ->method('getFrontendUsername')
            ->willReturn($frontendUsername)
        ;

        $tokenChecker
            ->method('isPreviewMode')
            ->willReturn(true)
        ;

        return $tokenChecker;
    }

    /**
     * @param class-string<User> $userClass
     */
    private function mockSecurity(bool $canShare = false, string|null $userClass = BackendUser::class, array $roles = ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH_MEMBER', 'IS_AUTHENTICATED_FULLY']): Security
    {
        $user = null;

        if (null !== $userClass) {
            $user = $this->createStub($userClass);
        }

        if ($canShare) {
            $security = $this->createMock(Security::class);
            $security
                ->expects($this->exactly(2))
                ->method('isGranted')
                ->willReturnMap([
                    ['ROLE_ALLOWED_TO_SWITCH_MEMBER', null, true],
                    [ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'preview_link', $canShare],
                ])
            ;
        } else {
            $security = $this->createStub(Security::class);
            $security
                ->method('isGranted')
                ->willReturnCallback(static fn (string $role): bool => \in_array($role, $roles, true))
            ;
        }

        $security
            ->method('getUser')
            ->willReturn($user)
        ;

        return $security;
    }

    private function getTwigMock(): Environment&Stub
    {
        $twig = $this->createStub(Environment::class);
        $twig
            ->method('render')
            ->willReturnCallback(static fn (string $name, array $data = []): string => json_encode(['name' => $name, 'data' => $data], JSON_THROW_ON_ERROR))
        ;

        return $twig;
    }

    private function mockTokenManager(): ContaoCsrfTokenManager&Stub
    {
        $tokenManager = $this->createStub(ContaoCsrfTokenManager::class);
        $tokenManager
            ->method('getDefaultTokenValue')
            ->willReturn('csrf')
        ;

        return $tokenManager;
    }

    private function mockTranslator(): TranslatorInterface
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(static fn (string $translation): string => $translation)
        ;

        return $translator;
    }

    private function mockFrontendPreviewAuthenticator(): FrontendPreviewAuthenticator&Stub
    {
        $authenticator = $this->createStub(FrontendPreviewAuthenticator::class);
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
