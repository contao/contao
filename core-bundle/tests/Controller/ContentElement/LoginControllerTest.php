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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class LoginControllerTest extends ContentElementTestCase
{
    public function testReturnsIfTheUserIsNotAFrontendUser(): void
    {
        $response = $this->renderWithModelData(
            new LoginController(
                $this->mockSecurity($this->createMock(BackendUser::class)),
                $this->createMock(UriSigner::class),
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->createMock(TranslatorInterface::class),
                $this->createMock(ContentUrlGenerator::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
            ],
        );

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testShowsLoginForm(): void
    {
        $response = $this->renderWithModelData(
            new LoginController(
                $this->mockSecurity(),
                $this->createMock(UriSigner::class),
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $this->createMock(ContentUrlGenerator::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'id' => 42,
                'type' => 'login',
            ],
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
                $this->mockSecurity(),
                $uriSigner,
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $this->createMock(ContentUrlGenerator::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
                'redirectBack' => 1,
            ],
            request: $request,
        );

        $content = $response->getContent();

        $this->assertTrue(str_contains($content, '<div class="content-login login">'));
        $this->assertTrue(str_contains($content, '<form action id="tl_login_" method="post">'));
        $this->assertTrue(str_contains($content, '<input type="hidden" name="FORM_SUBMIT" value="tl_login_">'));
        $this->assertTrue(str_contains($content, '<input type="hidden" name="_target_path" value="'.base64_encode('redirect_back').'">'));
        $this->assertTrue(str_contains($content, '<label for="username">translated(contao_default:MSC.username)</label>'));
        $this->assertTrue(str_contains($content, '<input type="text" name="username" id="username" class="text" value="" autocapitalize="off" autocomplete="username" required>'));
        $this->assertTrue(str_contains($content, '<label for="password">translated(contao_default:MSC.password.0)</label>'));
        $this->assertTrue(str_contains($content, '<input type="password" name="password" id="password" class="text password" value="" autocomplete="current-password" required>'));
        $this->assertTrue(str_contains($content, '<button type="submit" class="submit">MSC.login</button>'));
        $this->assertTrue(str_contains($content, '<button type="button" class="passkey-login">translated(contao_default:MSC.passkeyLogin)</button>'));

        $this->assertFalse(str_contains($content, '<p class="login_info">'));
    }

    public function testUsesRedirectPageTargetPath(): void
    {
        $jumpTo = $this->mockClassWithProperties(PageModel::class, ['id']);
        $jumpTo->id = 1;

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

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($jumpTo, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://jump-to-test.com/foobar')
        ;

        $response = $this->renderWithModelData(
            new LoginController(
                $this->mockSecurity(),
                $this->createMock(UriSigner::class),
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $contentUrlGenerator,
                $this->createMock(EventDispatcherInterface::class),
                $contaoFramework,
            ),
            [
                'type' => 'login',
                'jumpTo' => 1,
            ],
        );

        $content = $response->getContent();

        $this->assertTrue(str_contains($content, '<div class="content-login login">'));
        $this->assertTrue(str_contains($content, '<form action id="tl_login_" method="post">'));
        $this->assertTrue(str_contains($content, '<input type="hidden" name="FORM_SUBMIT" value="tl_login_">'));
        $this->assertTrue(str_contains($content, '<input type="hidden" name="_target_path" value="'.base64_encode('https://jump-to-test.com/foobar').'">'));
        $this->assertTrue(str_contains($content, '<label for="username">translated(contao_default:MSC.username)</label>'));
        $this->assertTrue(str_contains($content, '<input type="text" name="username" id="username" class="text" value="" autocapitalize="off" autocomplete="username" required>'));
        $this->assertTrue(str_contains($content, '<label for="password">translated(contao_default:MSC.password.0)</label>'));
        $this->assertTrue(str_contains($content, '<input type="password" name="password" id="password" class="text password" value="" autocomplete="current-password" required>'));
        $this->assertTrue(str_contains($content, '<button type="submit" class="submit">MSC.login</button>'));
        $this->assertTrue(str_contains($content, '<button type="button" class="passkey-login">translated(contao_default:MSC.passkeyLogin)</button>'));

        $this->assertFalse(str_contains($content, '<p class="login_info">'));
    }

    public function testShowsLogoutFormIfFrontendUserIsLoggedIn(): void
    {
        $user = $this->createMock(FrontendUser::class);

        $response = $this->renderWithModelData(
            new LoginController(
                $this->mockSecurity($user, false, false),
                $this->createMock(UriSigner::class),
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $this->createMock(ContentUrlGenerator::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
            ],
        );

        $content = $response->getContent();

        $this->assertTrue(str_contains($content, '<div class="content-login logout">'));
        $this->assertTrue(str_contains($content, '<p class="login_info">translated(contao_default:MSC.loggedInAs[])<br>January 1, 2032 01:01</p>'));
        $this->assertTrue(str_contains($content, '<button type="submit" class="submit">MSC.logout</button>'));
    }

    public function testShowsTwoFactorCodeFormIfTowFactorInProgress(): void
    {
        $user = $this->createMock(FrontendUser::class);

        $response = $this->renderWithModelData(
            new LoginController(
                $this->mockSecurity($user, false, true),
                $this->createMock(UriSigner::class),
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $this->createMock(ContentUrlGenerator::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
            ],
        );

        $content = $response->getContent();

        $this->assertTrue(str_contains($content, '<h3>translated(contao_default:MSC.twoFactorAuthentication)</h3>'));
        $this->assertTrue(str_contains($content, '<label for="verify">translated(contao_default:MSC.twoFactorVerification)</label>'));
        $this->assertTrue(str_contains($content, '<input type="text" name="verify" id="verify" class="text" value="" autocapitalize="off" autocomplete="one-time-code" required>'));
        $this->assertTrue(str_contains($content, '<button type="submit" class="submit">MSC.continue</button>'));
    }

    public function testShowsLogout(): void
    {
        $user = $this->createMock(FrontendUser::class);

        $response = $this->renderWithModelData(
            new LoginController(
                $this->mockSecurity($user, false, false),
                $this->createMock(UriSigner::class),
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $this->createMock(ContentUrlGenerator::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
            ],
        );

        $content = $response->getContent();

        $this->assertTrue(str_contains($content, '<div class="content-login logout">'));
        $this->assertTrue(str_contains($content, '<form action id="tl_logout_" method="post">'));
        $this->assertTrue(str_contains($content, '<input type="hidden" name="FORM_SUBMIT" value="tl_logout_">'));
        $this->assertTrue(str_contains($content, '<input type="hidden" name="_target_path" value="http://:/">'));
        $this->assertTrue(str_contains($content, '<p class="login_info">translated(contao_default:MSC.loggedInAs[])<br>January 1, 2032 01:01</p>'));
        $this->assertTrue(str_contains($content, '<button type="submit" class="submit">MSC.logout</button>'));
    }

    public function testUsesRedirectBackTargetPathForLogout(): void
    {
        $user = $this->createMock(FrontendUser::class);

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
                $this->mockSecurity($user, false, false),
                $uriSigner,
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $this->createMock(ContentUrlGenerator::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
                'redirectBack' => 1,
            ],
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
        $user = $this->createMock(FrontendUser::class);

        $page = $this->mockClassWithProperties(PageModel::class, ['protected']);
        $page->protected = true;

        $request = Request::create('https://protected-target-path-test.com/foobar');

        $response = $this->renderWithModelData(
            new LoginController(
                $this->mockSecurity($user, false, false),
                $this->createMock(UriSigner::class),
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $this->createMock(ContentUrlGenerator::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
            ],
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
        $user = $this->createMock(FrontendUser::class);

        $page = $this->mockClassWithProperties(PageModel::class, ['protected']);
        $page->type = 'error_401';

        $response = $this->renderWithModelData(
            new LoginController(
                $this->mockSecurity($user, true, false),
                $this->createMock(UriSigner::class),
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->mockTranslator(),
                $this->createMock(ContentUrlGenerator::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
            ],
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

    private function mockSecurity(User|null $user = null, bool|null $isRemembered = null, bool|null $twoFaInProgress = null): Security&MockObject
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $isGrantedMap = [];

        if (null !== $isRemembered) {
            $isGrantedMap[] = ['IS_REMEMBERED', null, $isRemembered];
        }

        if (null !== $twoFaInProgress) {
            $isGrantedMap[] = ['IS_AUTHENTICATED_2FA_IN_PROGRESS', null, $twoFaInProgress];
        }

        if ($isGrantedMap) {
            $security
                ->expects($this->exactly(\count($isGrantedMap)))
                ->method('isGranted')
                ->willReturnMap($isGrantedMap)
            ;
        }

        if ($twoFaInProgress) {
            $security
                ->expects($this->once())
                ->method('getToken')
                ->willReturn($this->createMock(TokenInterface::class))
            ;
        }

        return $security;
    }
}
