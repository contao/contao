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
use Contao\Config;
use Contao\CoreBundle\Controller\AbstractBackendController;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Database;
use Contao\Environment as ContaoEnvironment;
use Contao\System;
use Contao\TemplateLoader;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

class AbstractBackendControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->backupServerEnvGetPost();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_LANGUAGE'], $GLOBALS['TL_MIME']);

        $this->restoreServerEnvGetPost();
        $this->resetStaticProperties([ContaoEnvironment::class, BackendUser::class, Database::class, System::class, Config::class, TemplateLoader::class]);

        parent::tearDown();
    }

    public function testAddsAndMergesBackendContext(): void
    {
        $controller = new class() extends AbstractBackendController {
            public function fooAction(): Response
            {
                return $this->render('custom_be.html.twig', ['foo' => 'bar', 'version' => 'my version']);
            }
        };

        // Legacy setup
        ContaoEnvironment::reset();

        $filesystem = new Filesystem();
        $filesystem->mkdir(Path::join($this->getTempDir(), 'languages/en'));
        $filesystem->touch(Path::join($this->getTempDir(), 'be_main.html5'));

        $GLOBALS['TL_LANG']['MSC'] = [
            'version' => 'version',
            'dashboard' => 'dashboard',
            'home' => 'home',
            'learnMore' => 'learn more',
        ];

        $GLOBALS['TL_LANGUAGE'] = 'en';

        $_SERVER['HTTP_HOST'] = 'localhost';

        TemplateLoader::addFile('be_main', '');

        $expectedContext = [
            'version' => 'my version',
            'headline' => 'dashboard',
            'title' => '',
            'theme' => 'flexible',
            'language' => 'en',
            'host' => 'localhost',
            'charset' => 'UTF-8',
            'home' => 'home',
            'isPopup' => null,
            'learnMore' => 'learn more',
            'menu' => '<menu>',
            'headerMenu' => '<header_menu>',
            'badgeTitle' => '',
            'foo' => 'bar',
        ];

        $container = $this->getContainerWithDefaultConfiguration($expectedContext);

        System::setContainer($container);
        $controller->setContainer($container);

        $this->assertSame('<custom_be_main>', $controller->fooAction()->getContent());
    }

    /**
     * @dataProvider provideRequests
     */
    public function testHandlesTurboRequests(Request $request, bool|null $includeChromeContext, array $expectedContext, string $expectedRequestFormat = 'html'): void
    {
        $controller = new class() extends AbstractBackendController {
            public function fooAction(bool|null $includeChromeContext): Response
            {
                return $this->render(
                    'custom_be.html.twig',
                    ['version' => 'my version'],
                    includeChromeContext: $includeChromeContext,
                );
            }
        };

        // Legacy setup
        ContaoEnvironment::reset();

        $filesystem = new Filesystem();
        $filesystem->mkdir(Path::join($this->getTempDir(), 'languages/en'));
        $filesystem->touch(Path::join($this->getTempDir(), 'be_main.html5'));

        $GLOBALS['TL_LANG']['MSC'] = [
            'version' => 'version',
            'dashboard' => 'dashboard',
            'home' => 'home',
            'learnMore' => 'learn more',
        ];

        $GLOBALS['TL_LANGUAGE'] = 'en';

        TemplateLoader::addFile('be_main', '');

        $container = $this->getContainerWithDefaultConfiguration($expectedContext, $request);

        System::setContainer($container);
        $controller->setContainer($container);

        $this->assertSame('<custom_be_main>', $controller->fooAction($includeChromeContext)->getContent());
        $this->assertSame($expectedRequestFormat, $request->getRequestFormat());
    }

    public static function provideRequests(): iterable
    {
        $defaultContext = [
            'headline' => 'dashboard',
            'title' => '',
            'theme' => 'flexible',
            'language' => 'en',
            'host' => 'localhost',
            'charset' => 'UTF-8',
            'home' => 'home',
            'isPopup' => null,
            'learnMore' => 'learn more',
            'menu' => '<menu>',
            'headerMenu' => '<header_menu>',
            'badgeTitle' => '',
        ];

        $customContext = [
            'version' => 'my version',
        ];

        $turboFrameRequest = new Request(server: ['HTTP_HOST' => 'localhost']);
        $turboFrameRequest->headers->set('Turbo-Frame', 'id');

        yield 'default turbo frame' => [
            $turboFrameRequest,
            null,
            $customContext,
        ];

        yield 'turbo frame with chrome' => [
            $turboFrameRequest,
            true,
            [...$customContext, ...$defaultContext],
        ];

        yield 'default turbo frame explicitly without chrome' => [
            $turboFrameRequest,
            false,
            $customContext,
        ];

        $turboStreamRequest = new Request(server: ['HTTP_HOST' => 'localhost']);
        $turboStreamRequest->headers->set('Accept', 'text/vnd.turbo-stream.html; charset=utf-8');

        yield 'default turbo stream' => [
            $turboStreamRequest,
            null,
            $customContext,
            'turbo_stream',
        ];

        yield 'turbo stream with chrome' => [
            $turboStreamRequest,
            true,
            [...$customContext, ...$defaultContext],
            'turbo_stream',
        ];

        yield 'turbo stream explicitly without chrome' => [
            $turboStreamRequest,
            false,
            $customContext,
            'turbo_stream',
        ];

        $plainRequest = new Request(server: ['HTTP_HOST' => 'localhost']);

        yield 'plain request' => [
            $plainRequest,
            null,
            [...$customContext, ...$defaultContext],
        ];

        yield 'plain request explicitly with chrome' => [
            $plainRequest,
            true,
            [...$customContext, ...$defaultContext],
        ];

        yield 'plain request without chrome' => [
            $plainRequest,
            false,
            $customContext,
        ];
    }

    public function testGetSessionBag(): void
    {
        $controller = new class() extends AbstractBackendController {
            public function getBackendSessionBagDelegate(): AttributeBagInterface|null
            {
                return $this->getBackendSessionBag();
            }
        };

        $sessionBag = new ArrayAttributeBag();
        $sessionBag->setName('contao_backend');

        $sessionStorage = new MockArraySessionStorage();
        $sessionStorage->registerBag($sessionBag);

        $request = new Request();
        $request->setSession(new Session($sessionStorage));

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = new ContainerBuilder();
        $container->set('request_stack', $requestStack);

        $controller->setContainer($container);

        $this->assertSame($sessionBag, $controller->getBackendSessionBagDelegate());
    }

    public function testSetsUnprocessableEntityStatus(): void
    {
        $controller = new class() extends AbstractBackendController {
            public function fooAction(): Response
            {
                return $this->render('custom_be.html.twig', ['foo' => 'bar', 'version' => 'my version']);
            }
        };

        // Legacy setup
        ContaoEnvironment::reset();

        $filesystem = new Filesystem();
        $filesystem->mkdir(Path::join($this->getTempDir(), 'languages/en'));
        $filesystem->touch(Path::join($this->getTempDir(), 'be_main.html5'));

        $GLOBALS['TL_LANG']['MSC'] = [
            'version' => 'version',
            'dashboard' => 'dashboard',
            'home' => 'home',
            'learnMore' => 'learn more',
        ];

        $GLOBALS['TL_LANGUAGE'] = 'en';

        $_SERVER['HTTP_HOST'] = 'localhost';

        TemplateLoader::addFile('be_main', '');

        $expectedContext = [
            'version' => 'my version',
            'headline' => 'dashboard',
            'title' => '',
            'theme' => 'flexible',
            'language' => 'en',
            'host' => 'localhost',
            'charset' => 'UTF-8',
            'home' => 'home',
            'isPopup' => null,
            'learnMore' => 'learn more',
            'menu' => '<menu>',
            'headerMenu' => '<header_menu>',
            'badgeTitle' => '',
            'foo' => 'bar',
        ];

        $container = $this->getContainerWithDefaultConfiguration($expectedContext);

        $container->get('request_stack')->getMainRequest()->attributes->set('_contao_widget_error', true);

        System::setContainer($container);
        $controller->setContainer($container);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $controller->fooAction()->getStatusCode());
    }

    private function getContainerWithDefaultConfiguration(array $expectedContext, Request|null $request = null): ContainerBuilder
    {
        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true)
        ;

        $twig = $this->createMock(Environment::class);
        $twig
            ->method('render')
            ->willReturnCallback(
                function (string $template, array $context) use ($expectedContext) {
                    if ('custom_be.html.twig' === $template) {
                        $this->assertSame($expectedContext, $context);
                    }

                    $map = [
                        '@Contao/backend/chrome/main_menu.html.twig' => '<menu>',
                        '@Contao/backend/chrome/header_menu.html.twig' => '<header_menu>',
                        'custom_be.html.twig' => '<custom_be_main>',
                    ];

                    return $map[$template];
                },
            )
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request ?? new Request(server: $_SERVER));

        $container->set('security.authorization_checker', $authorizationChecker);
        $container->set('security.token_storage', $this->createMock(TokenStorageInterface::class));
        $container->set('contao.security.token_checker', $this->createMock(TokenChecker::class));
        $container->set('database_connection', $this->createMock(Connection::class));
        $container->set('session', $this->createMock(Session::class));
        $container->set('twig', $twig);
        $container->set('router', $this->createMock(RouterInterface::class));
        $container->set('request_stack', $requestStack);

        $container->setParameter('contao.resources_paths', $this->getTempDir());

        return $container;
    }
}
