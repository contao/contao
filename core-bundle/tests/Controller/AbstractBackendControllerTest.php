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
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\Database;
use Contao\Environment as ContaoEnvironment;
use Contao\System;
use Contao\TemplateLoader;
use Doctrine\DBAL\Driver\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Constraint\IsAnything;
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
            'Template' => $this->anything(),
            'getLocaleString' => $this->anything(),
            'getDateString' => $this->anything(),
            'as_editor_view' => true,
        ];

        $container = $this->getContainerWithDefaultConfiguration($expectedContext);

        System::setContainer($container);
        $controller->setContainer($container);

        $this->assertSame('<result>', $controller->fooAction()->getContent());
    }

    #[DataProvider('provideRequests')]
    public function testHandlesTurboRequests(Request $request, string $view, bool|null $includeChromeContext, array $expectedContext, string $expectedRequestFormat = 'html', int $expectedStatus = Response::HTTP_OK, Response|null $response = null): void
    {
        $controller = new class() extends AbstractBackendController {
            public function fooAction(string $view, bool|null $includeChromeContext, Response|null $response = null): Response
            {
                return $this->render(
                    $view,
                    ['version' => 'my version'],
                    $response,
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
        $response = $controller->fooAction($view, $includeChromeContext, $response);

        $this->assertSame('<result>', $response->getContent());
        $this->assertSame($expectedRequestFormat, $request->getRequestFormat());
        $this->assertSame($expectedStatus, $response->getStatusCode());
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
            'Template' => self::anything(),
            'getLocaleString' => self::anything(),
            'getDateString' => self::anything(),
            'as_editor_view' => true,
        ];

        $customContext = [
            'version' => 'my version',
        ];

        $plainRequest = new Request(server: ['HTTP_HOST' => 'localhost']);

        yield 'plain request' => [
            clone $plainRequest,
            'custom_be.html.twig',
            null,
            [...$customContext, ...$defaultContext],
        ];

        yield 'plain request explicitly with chrome' => [
            clone $plainRequest,
            'custom_be.html.twig',
            true,
            [...$customContext, ...$defaultContext],
        ];

        yield 'plain request without chrome' => [
            clone $plainRequest,
            'custom_be.html.twig',
            false,
            $customContext,
        ];

        yield 'request with widget error' => [
            new Request(attributes: ['_contao_widget_error' => true], server: ['HTTP_HOST' => 'localhost']),
            'custom_be.html.twig',
            false,
            $customContext,
            'html',
            Response::HTTP_UNPROCESSABLE_ENTITY,
        ];

        yield 'request with widget error and 500 response' => [
            new Request(attributes: ['_contao_widget_error' => true], server: ['HTTP_HOST' => 'localhost']),
            'custom_be.html.twig',
            false,
            $customContext,
            'html',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            new Response(status: Response::HTTP_INTERNAL_SERVER_ERROR),
        ];

        $requestAcceptingTurboStreams = new Request(server: ['HTTP_HOST' => 'localhost']);
        $requestAcceptingTurboStreams->headers->set('Accept', 'text/vnd.turbo-stream.html; charset=utf-8');

        yield 'regular request accepting turbo stream' => [
            clone $requestAcceptingTurboStreams,
            'custom_be.html.twig',
            null,
            [...$customContext, ...$defaultContext],
        ];

        yield 'turbo stream with chrome' => [
            clone $requestAcceptingTurboStreams,
            'update.stream.html.twig',
            true,
            [...$customContext, ...$defaultContext],
            'turbo_stream',
        ];

        yield 'turbo stream explicitly without chrome' => [
            clone $requestAcceptingTurboStreams,
            'update.stream.html.twig',
            false,
            $customContext,
            'turbo_stream',
        ];
    }

    public function testThrowsAnExceptionWhenRenderingAStreamWithoutBeingAccepted(): void
    {
        $controller = new class() extends AbstractBackendController {
            public function fooAction(string $view, bool|null $includeChromeContext, Response|null $response = null): Response
            {
                return $this->render(
                    $view,
                    ['version' => 'my version'],
                    $response,
                    includeChromeContext: $includeChromeContext,
                );
            }
        };

        $plainRequest = new Request(server: ['HTTP_HOST' => 'localhost']);

        $container = $this->getContainerWithDefaultConfiguration([], $plainRequest);
        $controller->setContainer($container);

        $this->expectException(\LogicException::class);
        $controller->fooAction('update.stream.html.twig', null, null);
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
                        // Normalize context
                        foreach ($expectedContext as $key => $value) {
                            if ($value instanceof IsAnything && null !== ($context[$key] ?? null)) {
                                $context[$key] = $value;
                            }
                        }

                        ksort($expectedContext);
                        ksort($context);

                        $this->assertSame($expectedContext, $context);
                    }

                    $map = [
                        '@Contao/backend/chrome/main_menu.html.twig' => '<menu>',
                        '@Contao/backend/chrome/header_menu.html.twig' => '<header_menu>',
                        'custom_be.html.twig' => '<result>',
                        'update.stream.html.twig' => '<result>',
                    ];

                    return $map[$template];
                },
            )
        ;

        $filesystemLoader = $this->createMock(ContaoFilesystemLoader::class);
        $filesystemLoader
            ->method('exists')
            ->willReturn(true)
        ;

        $filesystemLoader
            ->method('getFirst')
            ->willReturnCallback(
                static fn (string $identifier) => "templates/$identifier.html5",
            )
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request ?? new Request(server: $_SERVER));

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->method('isBackendRequest')
            ->willReturn(true)
        ;

        $container->set('security.authorization_checker', $authorizationChecker);
        $container->set('security.token_storage', $this->createMock(TokenStorageInterface::class));
        $container->set('contao.security.token_checker', $this->createMock(TokenChecker::class));
        $container->set('database_connection', $this->createMock(Connection::class));
        $container->set('session', $this->createMock(Session::class));
        $container->set('twig', $twig);
        $container->set('contao.twig.filesystem_loader', $filesystemLoader);
        $container->set('router', $this->createMock(RouterInterface::class));
        $container->set('request_stack', $requestStack);
        $container->set('contao.routing.scope_matcher', $scopeMatcher);

        $container->setParameter('contao.resources_paths', $this->getTempDir());

        return $container;
    }
}
