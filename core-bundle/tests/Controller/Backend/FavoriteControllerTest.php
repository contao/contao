<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\Backend;

use Contao\BackendUser;
use Contao\Controller;
use Contao\CoreBundle\Controller\Backend\FavoriteController;
use Contao\CoreBundle\Exception\BadRequestException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Util\UrlUtil;
use Contao\DC_Table;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Environment;
use Twig\Template;
use Twig\TemplateWrapper;

class FavoriteControllerTest extends TestCase
{
    public function testReturnsEmptyResponseWithoutBackendUser(): void
    {
        $container = $this->getContainer(null, [], null, false);
        $connection = $this->createStub(Connection::class);

        $controller = new FavoriteController($this->createContaoFrameworkStub(), $connection);
        $controller->setContainer($container);

        $response = $controller(new Request());

        $this->assertEmpty($response->getContent());
    }

    public function testThrowsExceptionWithoutTargetPath(): void
    {
        $container = $this->getContainer();
        $connection = $this->createStub(Connection::class);
        $request = new Request();

        $controller = new FavoriteController($this->createContaoFrameworkStub(), $connection);
        $controller->setContainer($container);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Missing target_path in request.');

        $controller($request);
    }

    #[DataProvider('favoriteProvider')]
    public function testRendersAddToFavoriteButton(Request $request, int|false $currentId, array $parameters, string|null $block = 'form', string $expectedResponse = Response::class): void
    {
        $controllerAdapter = $this->createAdapterStub(['loadDataContainer']);
        $dataContainer = $this->createClassWithPropertiesStub(DC_Table::class);

        $framework = $this->createContaoFrameworkStub([Controller::class => $controllerAdapter], [DC_Table::class => $dataContainer]);

        $queries = [[
            'SELECT id FROM tl_favorites WHERE url = :url AND user = :user',
            [
                'url' => UrlUtil::getNormalizePathAndQuery($request->get('target_path')),
                'user' => 42,
            ],
            $currentId,
        ]];

        if ($currentId && 'success_stream' === $block) {
            $queries[] = ['SELECT COUNT(*) FROM tl_favorites WHERE user=?', [42], 0];
        }

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(\count($queries)))
            ->method('fetchOne')
            ->willReturnMap($queries)
        ;

        $controller = new FavoriteController($framework, $connection);

        $container = $this->getContainer($block, $parameters, $this->mockRouter(false === $currentId || $request->isMethod('POST') ? $request->get('target_path') : null));
        $controller->setContainer($container);

        $response = $controller($request);

        $this->assertInstanceOf($expectedResponse, $response);
    }

    public static function favoriteProvider(): iterable
    {
        yield 'Generates add button if URL is not found' => [
            self::getRequest('/foo/bar'),
            false,
            [
                'action' => '/add/favorite',
                'target_path' => '/foo/bar',
                'active' => false,
            ],
        ];

        yield 'Normalizes URL when generating add button' => [
            self::getRequest('/foo/bar?foo=bar&baz=foo'),
            false,
            [
                'action' => '/add/favorite',
                'target_path' => '/foo/bar?baz=foo&foo=bar',
                'active' => false,
            ],
        ];

        yield 'Uses query parameters when generating add button' => [
            self::getRequest('/foo/bar?foo=bar&baz=foo', false, false, true),
            false,
            [
                'action' => '/add/favorite',
                'target_path' => '/foo/bar?baz=foo&foo=bar',
                'active' => false,
            ],
        ];

        yield 'Generates remove button if URL is found' => [
            self::getRequest('/foo/bar'),
            21,
            [
                'action' => '/remove/favorite',
                'target_path' => '/foo/bar',
                'active' => true,
            ],
        ];

        yield 'POST redirects back to the original URL' => [
            self::getRequest('/foo/bar', true),
            21,
            [],
            null,
            RedirectResponse::class,
        ];

        yield 'POST renders Turbo Stream' => [
            self::getRequest('/foo/bar', true, true),
            21,
            [
                'id' => 21,
                'active' => false,
                'action' => '/add/favorite',
                'empty' => true,
            ],
            'success_stream',
        ];
    }

    private function getContainer(string|null $block = null, array $parameters = [], UrlGeneratorInterface|null $urlGenerator = null, bool $backendUser = true): ContainerBuilder
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($backendUser ? $this->createClassWithPropertiesStub(BackendUser::class, ['id' => 42]) : null)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $template = $this->createMock(Template::class);
        $template
            ->expects(null === $block ? $this->never() : $this->once())
            ->method('renderBlock')
            ->with($block, $parameters)
        ;

        $twig = $this->createStub(Environment::class);
        $twig
            ->method('load')
            ->with('@Contao/backend/chrome/favorite.html.twig')
            ->willReturn(new TemplateWrapper($twig, $template))
        ;

        if (!$urlGenerator) {
            $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
            $urlGenerator
                ->expects($this->never())
                ->method('generate')
            ;
        }

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('security.token_storage', $tokenStorage);
        $container->set('twig', $twig);
        $container->set('router', $urlGenerator);

        return $container;
    }

    private static function getRequest(string $targetPath, bool $post = false, bool $turbo = false, bool $query = false): Request
    {
        $request = new Request();

        if ($post) {
            $request->setMethod('POST');
            $request->request->set('FORM_SUBMIT', 'remove-favorite');
            $request->request->set('target_path', $targetPath);

            if ($turbo) {
                $request->setRequestFormat('turbo_stream');
            }
        } elseif ($query) {
            $request->query->set('target_path', $targetPath);
        } else {
            $request->attributes->set('target_path', $targetPath);
        }

        return $request;
    }

    private function mockRouter(string|null $url): UrlGeneratorInterface&Stub
    {
        $router = $this->createStub(UrlGeneratorInterface::class);

        if ($url) {
            $router
                ->method('generate')
                ->with(
                    'contao_backend',
                    [
                        'do' => 'favorites',
                        'act' => 'paste',
                        'mode' => 'create',
                        'data' => base64_encode(UrlUtil::getNormalizePathAndQuery($url)),
                        'return' => '1',
                    ],
                )
                ->willReturn('/add/favorite')
            ;
        } else {
            $router
                ->method('generate')
                ->with(FavoriteController::class)
                ->willReturn('/remove/favorite')
            ;
        }

        return $router;
    }
}
