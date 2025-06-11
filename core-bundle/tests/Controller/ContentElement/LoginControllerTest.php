<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\ContentElement;

use Contao\BackendUser;
use Contao\CoreBundle\Controller\ContentElement\LoginController;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class LoginControllerTest extends ContentElementTestCase
{
    public function testReturnsIfTheUserIsNotAFrontendUser(): void
    {
        $response = $this->renderWithModelData(
            new LoginController(
                $this->createMock(UriSigner::class),
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->createMock(TranslatorInterface::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
            ],
            adjustedContainer: $this->getAdjustedContainer($this->createMock(BackendUser::class), null, null),
        );

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testShowsLoginForm(): void
    {
        $response = $this->renderWithModelData(
            new LoginController(
                $this->createMock(UriSigner::class),
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'id' => 42,
                'type' => 'login',
            ],
            adjustedContainer: $this->getAdjustedContainer(),
        );

        $content = $response->getContent();

        $this->assertTrue(str_contains($content, '<div class="content-login login">'));
        $this->assertTrue(str_contains($content, '<form action id="tl_login_42" method="post">'));
        $this->assertTrue(str_contains($content, '<input type="hidden" name="FORM_SUBMIT" value="tl_login_42">'));
        $this->assertTrue(str_contains($content, '<input type="hidden" name="_target_path" value="aHR0cDovLzov">'));
        $this->assertTrue(str_contains($content, '<label for="username">translated(contao_default:MSC.username)</label>'));
        $this->assertTrue(str_contains($content, '<input type="text" name="username" id="username" class="text" value="" autocapitalize="off" autocomplete="username" required>'));
        $this->assertTrue(str_contains($content, '<label for="password">translated(contao_default:MSC.password.0)</label>'));
        $this->assertTrue(str_contains($content, '<input type="password" name="password" id="password" class="text password" value="" autocomplete="current-password" required>'));
        $this->assertTrue(str_contains($content, '<button type="submit" class="submit">MSC.login</button>'));
        $this->assertTrue(str_contains($content, '<button type="button" class="passkey-login">translated(contao_default:MSC.passkeyLogin)</button>'));

        $this->assertFalse(str_contains($content, 'translated(contao_default:MSC.lostPassword)'));
        $this->assertFalse(str_contains($content, '<p class="login_info">'));
    }

    public function testUsesRedirectBackTargetPath(): void
    {
        $request = Request::create('https://redirect-back-test.com/foobar?redirect=redirect_back');

        $uriSigner = $this->createMock(UriSigner::class);
        $uriSigner
            ->expects($this->once())
            ->method('checkRequest')
            ->with($request)
            ->willReturn(true)
        ;

        $response = $this->renderWithModelData(
            new LoginController(
                $uriSigner,
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
                'redirectBack' => 1,
            ],
            adjustedContainer: $this->getAdjustedContainer(),
            request: $request,
        );

        $content = $response->getContent();

        $this->assertTrue(str_contains($content, '<input type="hidden" name="_target_path" value="'.base64_encode('redirect_back').'">'));
    }

    public function testUsesRedirectPageTargetPath(): void
    {
        $jumpTo = $this->mockClassWithProperties(PageModel::class, ['id', 'alias']);
        $jumpTo->id = 1;
        $jumpTo->alias = 'foobar';

        $pageAdapter = $this->mockAdapter(['findById']);
        $pageAdapter
            ->expects($this->exactly(2))
            ->method('findById')
            ->willReturnCallback(
                static function (int|null $pageId) use ($jumpTo): PageModel|null {
                    if (1 === $pageId) {
                        return $jumpTo;
                    }

                    return null;
                },
            )
        ;

        $contaoFramework = $this->mockContaoFramework([
            PageModel::class => $pageAdapter,
        ]);

        $response = $this->renderWithModelData(
            new LoginController(
                $this->createMock(UriSigner::class),
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $this->createMock(EventDispatcherInterface::class),
                $contaoFramework,
            ),
            [
                'type' => 'login',
                'jumpTo' => 1,
            ],
            adjustedContainer: $this->getAdjustedContainer(pageForContentUrl: $jumpTo),
        );

        $content = $response->getContent();

        $this->assertTrue(str_contains($content, '<input type="hidden" name="_target_path" value="'.base64_encode('https://example.com/foobar').'">'));
    }

    public function testUsesRedirectBackTargetPathFromPostRequest(): void
    {
        $request = Request::create('https://target-path-post-test.com/login', 'POST', ['_target_path' => base64_encode('post_target_path')]);

        $uriSigner = $this->createMock(UriSigner::class);
        $uriSigner
            ->expects($this->never())
            ->method('checkRequest')
        ;

        $response = $this->renderWithModelData(
            new LoginController(
                $uriSigner,
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
                'redirectBack' => 1,
            ],
            adjustedContainer: $this->getAdjustedContainer(),
            request: $request,
        );

        $content = $response->getContent();

        $this->assertTrue(str_contains($content, '<input type="hidden" name="_target_path" value="'.base64_encode('post_target_path').'">'));
    }

    public function testUsesRedirectBackFromReferer(): void
    {
        $request = Request::create('https://request-referrer-test.com/login');
        $request->headers->set('referer', 'https://request-referrer-test.com/foobar');

        $uriSigner = $this->createMock(UriSigner::class);
        $uriSigner
            ->expects($this->never())
            ->method('checkRequest')
        ;

        $response = $this->renderWithModelData(
            new LoginController(
                $uriSigner,
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
                'redirectBack' => 1,
            ],
            adjustedContainer: $this->getAdjustedContainer(),
            request: $request,
        );

        $content = $response->getContent();

        $this->assertTrue(str_contains($content, '<input type="hidden" name="_target_path" value="'.base64_encode('https://request-referrer-test.com/foobar').'">'));
    }

    public function testDoesNotUseRedirectBackFromRefererFromDifferentDomain(): void
    {
        $request = Request::create('https://request-referrer-test.com/login');
        $request->headers->set('referer', 'https://other-domain.com/foobar');

        $uriSigner = $this->createMock(UriSigner::class);
        $uriSigner
            ->expects($this->never())
            ->method('checkRequest')
        ;

        $response = $this->renderWithModelData(
            new LoginController(
                $uriSigner,
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
                'redirectBack' => 1,
            ],
            adjustedContainer: $this->getAdjustedContainer(),
            request: $request,
        );

        $content = $response->getContent();

        $this->assertTrue(str_contains($content, '<input type="hidden" name="_target_path" value="'.base64_encode('https://request-referrer-test.com/login').'">'));
    }

    public function testShowsPasswordResetLink(): void
    {
        $pwResetPage = $this->mockClassWithProperties(PageModel::class, ['id']);
        $pwResetPage->id = 8472;
        $pwResetPage->alias = 'pw-reset';

        $pageAdapter = $this->mockAdapter(['findById']);
        $pageAdapter
            ->expects($this->exactly(2))
            ->method('findById')
            ->willReturnCallback(
                static function (int|null $pageId) use ($pwResetPage): PageModel|null {
                    if (8472 === $pageId) {
                        return $pwResetPage;
                    }

                    return null;
                },
            )
        ;

        $contaoFramework = $this->mockContaoFramework([
            PageModel::class => $pageAdapter,
        ]);

        $response = $this->renderWithModelData(
            new LoginController(
                $this->createMock(UriSigner::class),
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $this->createMock(EventDispatcherInterface::class),
                $contaoFramework,
            ),
            [
                'type' => 'login',
                'pwResetPage' => 8472,
            ],
            adjustedContainer: $this->getAdjustedContainer(pageForContentUrl: $pwResetPage),
        );

        $content = $response->getContent();

        $this->assertTrue(str_contains($content, '<a href="https://example.com/pw-reset">translated(contao_default:MSC.lostPassword)</a>'));
    }

    #[DataProvider('getAuthenticationExceptions')]
    public function testShowsAuthenticationException(AuthenticationException $exception, string $message): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $session->setName('contao_frontend');

        $request = Request::create('https://auth-exception-test/login');
        $request->setSession($session);

        $authUtils = $this->createMock(AuthenticationUtils::class);
        $authUtils
            ->expects($this->once())
            ->method('getLastAuthenticationError')
            ->willReturn($exception)
        ;

        $authUtils
            ->expects($this->once())
            ->method('getLastUsername')
            ->willReturn('foobar')
        ;

        $response = $this->renderWithModelData(
            new LoginController(
                $this->createMock(UriSigner::class),
                $this->createMock(LogoutUrlGenerator::class),
                $authUtils,
                $this->mockTranslator(),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
            ],
            adjustedContainer: $this->getAdjustedContainer(),
            request: $request,
        );

        $content = $response->getContent();

        $this->assertTrue(str_contains($content, '<p class="error">'.$message.'</p>'));
        $this->assertSame($exception, $request->attributes->get(SecurityRequestAttributes::AUTHENTICATION_ERROR));
        $this->assertSame('foobar', $request->attributes->get(SecurityRequestAttributes::LAST_USERNAME));
    }

    public static function getAuthenticationExceptions(): iterable
    {
        yield [new TooManyLoginAttemptsAuthenticationException(), 'ERR.tooManyLoginAttempts'];
        yield [new InvalidTwoFactorCodeException(), 'ERR.invalidTwoFactor'];
        yield [new AuthenticationException(), 'ERR.invalidLogin'];
    }

    public function testShowsLogoutFormIfFrontendUserIsLoggedIn(): void
    {
        $response = $this->renderWithModelData(
            new LoginController(
                $this->createMock(UriSigner::class),
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
            ],
            adjustedContainer: $this->getAdjustedContainer($this->createMock(FrontendUser::class)),
        );

        $content = $response->getContent();

        $this->assertTrue(str_contains($content, '<div class="content-login logout">'));
        $this->assertTrue(str_contains($content, '<form action id="tl_logout_" method="post">'));
        $this->assertTrue(str_contains($content, '<input type="hidden" name="FORM_SUBMIT" value="tl_logout_">'));
        $this->assertTrue(str_contains($content, '<input type="hidden" name="_target_path" value="http://:/">'));
        $this->assertTrue(str_contains($content, '<p class="login_info">translated(contao_default:MSC.loggedInAs[])<br>January 1, 2032 01:01</p>'));
        $this->assertTrue(str_contains($content, '<button type="submit" class="submit">MSC.logout</button>'));
    }

    public function testShowsTwoFactorCodeFormIfTowFactorInProgress(): void
    {
        $response = $this->renderWithModelData(
            new LoginController(
                $this->createMock(UriSigner::class),
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
            ],
            adjustedContainer: $this->getAdjustedContainer($this->createMock(FrontendUser::class), false, true),
        );

        $content = $response->getContent();

        $this->assertTrue(str_contains($content, '<h3>translated(contao_default:MSC.twoFactorAuthentication)</h3>'));
        $this->assertTrue(str_contains($content, '<label for="verify">translated(contao_default:MSC.twoFactorVerification)</label>'));
        $this->assertTrue(str_contains($content, '<input type="text" name="verify" id="verify" class="text" value="" autocapitalize="off" autocomplete="one-time-code" required>'));
        $this->assertTrue(str_contains($content, '<button type="submit" class="submit">MSC.continue</button>'));
    }

    public function testUsesRedirectBackTargetPathForLogout(): void
    {
        $request = Request::create('https://redirect-back-test.com/foobar?redirect=redirect_back');

        $uriSigner = $this->createMock(UriSigner::class);
        $uriSigner
            ->expects($this->once())
            ->method('checkRequest')
            ->with($request)
            ->willReturn(true)
        ;

        $response = $this->renderWithModelData(
            new LoginController(
                $uriSigner,
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
                'redirectBack' => 1,
            ],
            adjustedContainer: $this->getAdjustedContainer($this->createMock(FrontendUser::class)),
            request: $request,
        );

        $content = $response->getContent();

        $this->assertTrue(str_contains($content, '<div class="content-login logout">'));
        $this->assertTrue(str_contains($content, '<form action id="tl_logout_" method="post">'));
        $this->assertTrue(str_contains($content, '<input type="hidden" name="FORM_SUBMIT" value="tl_logout_">'));
        $this->assertTrue(str_contains($content, '<input type="hidden" name="_target_path" value="redirect_back">'));
        $this->assertTrue(str_contains($content, '<p class="login_info">translated(contao_default:MSC.loggedInAs[])<br>January 1, 2032 01:01</p>'));
        $this->assertTrue(str_contains($content, '<button type="submit" class="submit">MSC.logout</button>'));
    }

    public function testUsesHomepageTargetPathForLogoutIfPageIsProtected(): void
    {
        $page = $this->mockClassWithProperties(PageModel::class, ['protected']);
        $page->protected = true;

        $request = Request::create('https://protected-target-path-test.com/foobar');

        $response = $this->renderWithModelData(
            new LoginController(
                $this->createMock(UriSigner::class),
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
            ],
            adjustedContainer: $this->getAdjustedContainer($this->createMock(FrontendUser::class)),
            page: $page,
            request: $request,
        );

        $content = $response->getContent();

        $this->assertTrue(str_contains($content, '<div class="content-login logout">'));
        $this->assertTrue(str_contains($content, '<form action id="tl_logout_" method="post">'));
        $this->assertTrue(str_contains($content, '<input type="hidden" name="FORM_SUBMIT" value="tl_logout_">'));
        $this->assertTrue(str_contains($content, '<input type="hidden" name="_target_path" value="https://protected-target-path-test.com/">'));
        $this->assertTrue(str_contains($content, '<p class="login_info">translated(contao_default:MSC.loggedInAs[])<br>January 1, 2032 01:01</p>'));
        $this->assertTrue(str_contains($content, '<button type="submit" class="submit">MSC.logout</button>'));
    }

    public function testShowsAuthenticateRemembered(): void
    {
        $page = $this->mockClassWithProperties(PageModel::class, ['protected']);
        $page->type = 'error_401';

        $response = $this->renderWithModelData(
            new LoginController(
                $this->createMock(UriSigner::class),
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
            ],
            adjustedContainer: $this->getAdjustedContainer($this->createMock(FrontendUser::class), true, false),
            page: $page,
        );

        $content = $response->getContent();

        $this->assertTrue(str_contains($content, '<div class="content-login login">'));
        $this->assertTrue(str_contains($content, '<form action id="tl_login_" method="post">'));
        $this->assertTrue(str_contains($content, '<input type="hidden" name="FORM_SUBMIT" value="tl_login_">'));
        $this->assertTrue(str_contains($content, '<input type="hidden" name="_target_path" value="aHR0cDovLzov">'));
        $this->assertTrue(str_contains($content, '<p class="login_info">translated(contao_default:MSC.loggedInAs[])<br>translated(contao_default:MSC.reauthenticate)</p>'));
        $this->assertTrue(str_contains($content, '<label for="password">translated(contao_default:MSC.password.0)</label>'));
        $this->assertTrue(str_contains($content, '<input type="password" name="password" id="password" class="text password" value="" autocomplete="current-password" required>'));
        $this->assertTrue(str_contains($content, '<button type="submit" class="submit">MSC.verify</button>'));
    }

    protected function getEnvironment(ContaoFilesystemLoader $contaoFilesystemLoader, ContaoFramework $framework): Environment
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->lastLogin = strtotime('2032-01-01 01:01:01');

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        $appVariable = new AppVariable();
        $appVariable->setTokenStorage($tokenStorage);

        $environment = parent::getEnvironment($contaoFilesystemLoader, $framework);
        $environment->addGlobal('app', $appVariable);

        return $environment;
    }

    private function mockTranslator(): TranslatorInterface&MockObject
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        return $translator;
    }

    private function getAdjustedContainer(User|null $user = null, bool|null $isRemembered = false, bool|null $twoFaInProgress = false, PageModel|null $pageForContentUrl = null): ContainerBuilder
    {
        $container = new ContainerBuilder();

        if ($user) {
            $token = $this->createMock(TokenInterface::class);
            $token
                ->expects($this->once())
                ->method('getUser')
                ->willReturn($user)
            ;
        } else {
            $token = null;
        }

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($twoFaInProgress ? $this->exactly(2) : $this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $container->set('security.token_storage', $tokenStorage);

        $isGrantedMap = [];

        if (null !== $isRemembered) {
            $isGrantedMap[] = ['IS_REMEMBERED', null, $isRemembered];
        }

        if (null !== $twoFaInProgress) {
            $isGrantedMap[] = ['IS_AUTHENTICATED_2FA_IN_PROGRESS', null, $twoFaInProgress];
        }

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker
            ->expects($this->exactly(\count($isGrantedMap)))
            ->method('isGranted')
            ->willReturnMap($isGrantedMap)
        ;

        $container->set('security.authorization_checker', $authChecker);

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator
            ->expects($pageForContentUrl ? $this->once() : $this->never())
            ->method('generate')
            ->willReturnCallback(
                static fn (PageModel $page): string => 'https://example.com/'.$page->alias,
            )
        ;

        $container->set('contao.routing.content_url_generator', $contentUrlGenerator);

        return $container;
    }
}
