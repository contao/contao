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
use Contao\CoreBundle\Controller\BackendPreviewController;
use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\TwoFactorAuthenticator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkDetails;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

class BackendPreviewControllerTest extends TestCase
{
    public function testRedirectsToPreviewEntrypoint(): void
    {
        $controller = new BackendPreviewController(
            '/preview.php',
            $this->createMock(FrontendPreviewAuthenticator::class),
            new EventDispatcher(),
            $this->mockSecurityHelper(),
            $this->createMock(LoginLinkHandlerInterface::class),
            $this->createMock(UriSigner::class),
        );

        $response = $controller(new Request());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/preview.php/', $response->getTargetUrl());
    }

    public function testAddsThePreviewEntrypointAtTheCorrectPosition(): void
    {
        $controller = new BackendPreviewController(
            '/preview.php',
            $this->createMock(FrontendPreviewAuthenticator::class),
            new EventDispatcher(),
            $this->mockSecurityHelper(),
            $this->createMock(LoginLinkHandlerInterface::class),
            $this->createMock(UriSigner::class),
        );

        $request = Request::create('https://localhost/managed-edition/public/contao/preview?page=123');
        $request->server->set('SCRIPT_NAME', '/managed-edition/public/index.php');
        $request->server->set('SCRIPT_FILENAME', '/managed-edition/public/index.php');

        $response = $controller($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/managed-edition/public/preview.php/contao/preview?page=123', $response->getTargetUrl());
    }

    public function testDeniesAccessIfNotGranted(): void
    {
        $controller = new BackendPreviewController(
            '/preview.php',
            $this->createMock(FrontendPreviewAuthenticator::class),
            new EventDispatcher(),
            $this->mockSecurityHelper(false),
            $this->createMock(LoginLinkHandlerInterface::class),
            $this->createMock(UriSigner::class),
        );

        $request = Request::create('https://localhost/preview.php/en/');
        $request->server->set('SCRIPT_NAME', '/preview.php');
        $request->server->set('SCRIPT_FILENAME', '/preview.php');

        $response = $controller($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testAuthenticatesWhenUserParameterGiven(): void
    {
        $previewAuthenticator = $this->createMock(FrontendPreviewAuthenticator::class);
        $previewAuthenticator
            ->expects($this->once())
            ->method('authenticateFrontendUser')
            ->willReturn(true)
        ;

        $request = Request::create('https://localhost/managed-edition/preview.php/en/');
        $request->query->set('user', '9');

        $request->server->set('SCRIPT_NAME', '/managed-edition/preview.php');
        $request->server->set('SCRIPT_FILENAME', '/managed-edition/preview.php');

        $controller = new BackendPreviewController(
            '/preview.php',
            $previewAuthenticator,
            new EventDispatcher(),
            $this->mockSecurityHelper(),
            $this->createMock(LoginLinkHandlerInterface::class),
            $this->createMock(UriSigner::class),
        );

        $response = $controller($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testDispatchesPreviewUrlConvertEvent(): void
    {
        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with($this->isInstanceOf(PreviewUrlConvertEvent::class))
        ;

        $controller = new BackendPreviewController(
            '/preview.php',
            $this->createMock(FrontendPreviewAuthenticator::class),
            $dispatcher,
            $this->mockSecurityHelper(),
            $this->createMock(LoginLinkHandlerInterface::class),
            $this->createMock(UriSigner::class),
        );

        $request = Request::create('https://localhost/preview.php/en/');
        $request->server->set('SCRIPT_NAME', '/preview.php');
        $request->server->set('SCRIPT_FILENAME', '/preview.php');

        $response = $controller($request);

        $this->assertTrue($response->isRedirection());
    }

    /**
     * @dataProvider redirectsFromPreviewUrlConvertEventListener
     */
    public function testRedirectsFromPreviewUrlConvertEvent(string $requestUrl, string $targetUrl, string $expectedLocation, string|null $loginUrl = null, bool $twoFactorComplete = false): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(
            ContaoCoreEvents::PREVIEW_URL_CONVERT,
            static function (PreviewUrlConvertEvent $event) use ($targetUrl): void {
                $event->setUrl($targetUrl);
            },
        );

        $loginLinkHandler = $this->createMock(LoginLinkHandlerInterface::class);
        $loginLinkHandler
            ->expects($loginUrl ? $this->once() : $this->never())
            ->method('createLoginLink')
            ->willReturn(new LoginLinkDetails($loginUrl ?? '', new \DateTimeImmutable()))
        ;

        $uriSigner = $this->createMock(UriSigner::class);
        $uriSigner
            ->method('sign')
            ->willReturnArgument(0)
        ;

        $controller = new BackendPreviewController(
            '/preview.php',
            $this->createMock(FrontendPreviewAuthenticator::class),
            $dispatcher,
            $this->mockSecurityHelper(true, $this->mockClassWithProperties(BackendUser::class), $twoFactorComplete),
            $loginLinkHandler,
            $uriSigner,
        );

        $request = Request::create($requestUrl);
        $request->server->set('SCRIPT_NAME', '/preview.php');
        $request->server->set('SCRIPT_FILENAME', '/preview.php');

        $response = $controller($request);

        $this->assertTrue($response->isRedirection());
        $this->assertSame($expectedLocation, $response->headers->get('Location'));
    }

    public function redirectsFromPreviewUrlConvertEventListener(): \Generator
    {
        yield 'Redirect to front end' => [
            'https://www.example.com/preview.php/contao/preview?page=17',
            'https://www.example.com/en/foo.html',
            'https://www.example.com/en/foo.html',
        ];

        yield 'Redirects to login link URL for cross-domain previews' => [
            'https://www.example.com/preview.php/contao/preview?page=42',
            'https://www.example.org/en/foo.html',
            'https://www.example.org/contao/login-link?_target_path='.urlencode(base64_encode('https://www.example.org/preview.php/contao/preview?page=42')).'&2fa_complete=0',
            'https://www.example.org/contao/login-link',
        ];

        yield 'Keeps 2FA state for cross-domain previews' => [
            'https://www.example.com/preview.php/contao/preview?page=42',
            'https://www.example.org/en/foo.html',
            'https://www.example.org/contao/login-link?_target_path='.urlencode(base64_encode('https://www.example.org/preview.php/contao/preview?page=42')).'&2fa_complete=1',
            'https://www.example.org/contao/login-link',
            true,
        ];
    }

    public function testRedirectsToRootPage(): void
    {
        $controller = new BackendPreviewController(
            '/preview.php',
            $this->createMock(FrontendPreviewAuthenticator::class),
            new EventDispatcher(),
            $this->mockSecurityHelper(),
            $this->createMock(LoginLinkHandlerInterface::class),
            $this->createMock(UriSigner::class),
        );

        $request = Request::create('https://localhost/preview.php/en/');
        $request->server->set('SCRIPT_NAME', '/preview.php');
        $request->server->set('SCRIPT_FILENAME', '/preview.php');

        $response = $controller($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/preview.php/', $response->getTargetUrl());
    }

    private function mockSecurityHelper(bool $granted = true, UserInterface|null $user = null, bool $twoFactorComplete = false): Security&MockObject
    {
        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->willReturn($granted)
        ;

        $security
            ->method('getUser')
            ->willReturn($user)
        ;

        if ($user) {
            $token = new UsernamePasswordToken($user, 'contao_backend');

            if ($twoFactorComplete) {
                $token->setAttribute(TwoFactorAuthenticator::FLAG_2FA_COMPLETE, true);
            }

            $security
                ->method('getToken')
                ->willReturn($token)
            ;
        }

        return $security;
    }
}
