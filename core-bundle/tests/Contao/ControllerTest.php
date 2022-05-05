<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DcaExtractor;
use Contao\DcaLoader;
use Contao\Environment;
use Contao\PageModel;
use Contao\System;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class ControllerTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function setUp(): void
    {
        parent::setUp();

        Controller::resetControllerCache();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_MIME']);

        $this->resetStaticProperties([DcaExtractor::class, DcaLoader::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testGeneratesTheMargin(): void
    {
        $margins = [
            'top' => '40px',
            'right' => '10%',
            'bottom' => '-2px',
            'left' => '-50%',
            'unit' => '',
        ];

        $this->assertSame('margin:40px 10% -2px -50%;', Controller::generateMargin($margins));
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddToUrlWithoutQueryString(): void
    {
        \define('TL_SCRIPT', '');

        $request = new Request();
        $request->attributes->set('_contao_referer_id', 'cri');

        $container = $this->getContainerWithContaoConfiguration();
        $container->get('request_stack')->push($request);

        System::setContainer($container);

        $this->assertSame('', Controller::addToUrl(''));
        $this->assertSame('?do=page&amp;ref=cri', Controller::addToUrl('do=page'));
        $this->assertSame('?do=page&amp;rt=foo&amp;ref=cri', Controller::addToUrl('do=page&amp;rt=foo'));
        $this->assertSame('?do=page&amp;ref=cri', Controller::addToUrl('do=page&amp;ref=bar'));
        $this->assertSame('?act=edit&amp;id=2&amp;ref=cri', Controller::addToUrl('act=edit&id=2'));
        $this->assertSame('?act=edit&amp;id=2&amp;ref=cri', Controller::addToUrl('act=edit&amp;id=2'));
        $this->assertSame('?act=edit&amp;foo=%2B&amp;bar=%20&amp;ref=cri', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20'));

        $this->assertSame('', Controller::addToUrl('', false));
        $this->assertSame('?do=page', Controller::addToUrl('do=page', false));
        $this->assertSame('?do=page&amp;rt=foo', Controller::addToUrl('do=page&amp;rt=foo', false));
        $this->assertSame('?do=page&amp;ref=bar', Controller::addToUrl('do=page&amp;ref=bar', false));
        $this->assertSame('?act=edit&amp;id=2', Controller::addToUrl('act=edit&id=2', false));
        $this->assertSame('?act=edit&amp;id=2', Controller::addToUrl('act=edit&amp;id=2', false));
        $this->assertSame('?act=edit&amp;foo=%2B&amp;bar=%20', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20', false));

        $request->query->set('ref', 'ref');

        $this->assertSame('?ref=cri', Controller::addToUrl('', false));
        $this->assertSame('?do=page&amp;ref=cri', Controller::addToUrl('do=page', false));
        $this->assertSame('?do=page&amp;rt=foo&amp;ref=cri', Controller::addToUrl('do=page&amp;rt=foo', false));
        $this->assertSame('?do=page&amp;ref=cri', Controller::addToUrl('do=page&amp;ref=bar', false));
        $this->assertSame('?act=edit&amp;id=2&amp;ref=cri', Controller::addToUrl('act=edit&id=2', false));
        $this->assertSame('?act=edit&amp;id=2&amp;ref=cri', Controller::addToUrl('act=edit&amp;id=2', false));
        $this->assertSame('?act=edit&amp;foo=%2B&amp;bar=%20&amp;ref=cri', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20', false));
    }

    /**
     * @runInSeparateProcess
     */
    public function testAddToUrlWithQueryString(): void
    {
        \define('TL_SCRIPT', '');

        $request = new Request();
        $request->attributes->set('_contao_referer_id', 'cri');
        $request->server->set('QUERY_STRING', 'do=page&id=4');

        $container = $this->getContainerWithContaoConfiguration();
        $container->get('request_stack')->push($request);

        System::setContainer($container);

        $this->assertSame('?do=page&amp;id=4', Controller::addToUrl(''));
        $this->assertSame('?do=page&amp;id=4&amp;ref=cri', Controller::addToUrl('do=page'));
        $this->assertSame('?do=page&amp;id=4&amp;rt=foo&amp;ref=cri', Controller::addToUrl('do=page&amp;rt=foo'));
        $this->assertSame('?do=page&amp;id=4&amp;ref=cri', Controller::addToUrl('do=page&amp;ref=bar'));
        $this->assertSame('?do=page&amp;id=2&amp;act=edit&amp;ref=cri', Controller::addToUrl('act=edit&id=2'));
        $this->assertSame('?do=page&amp;id=2&amp;act=edit&amp;ref=cri', Controller::addToUrl('act=edit&amp;id=2'));
        $this->assertSame('?do=page&amp;id=4&amp;act=edit&amp;foo=%2B&amp;bar=%20&amp;ref=cri', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20'));
        $this->assertSame('?do=page&amp;key=foo&amp;ref=cri', Controller::addToUrl('key=foo', true, ['id']));

        $this->assertSame('?do=page&amp;id=4', Controller::addToUrl('', false));
        $this->assertSame('?do=page&amp;id=4', Controller::addToUrl('do=page', false));
        $this->assertSame('?do=page&amp;id=4&amp;rt=foo', Controller::addToUrl('do=page&amp;rt=foo', false));
        $this->assertSame('?do=page&amp;id=4&amp;ref=bar', Controller::addToUrl('do=page&amp;ref=bar', false));
        $this->assertSame('?do=page&amp;id=2&amp;act=edit', Controller::addToUrl('act=edit&id=2', false));
        $this->assertSame('?do=page&amp;id=2&amp;act=edit', Controller::addToUrl('act=edit&amp;id=2', false));
        $this->assertSame('?do=page&amp;id=4&amp;act=edit&amp;foo=%2B&amp;bar=%20', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20', false));
        $this->assertSame('?do=page&amp;key=foo', Controller::addToUrl('key=foo', false, ['id']));

        $request->query->set('ref', 'ref');

        $this->assertSame('?do=page&amp;id=4&amp;ref=cri', Controller::addToUrl('', false));
        $this->assertSame('?do=page&amp;id=4&amp;ref=cri', Controller::addToUrl('do=page', false));
        $this->assertSame('?do=page&amp;id=4&amp;rt=foo&amp;ref=cri', Controller::addToUrl('do=page&amp;rt=foo', false));
        $this->assertSame('?do=page&amp;id=4&amp;ref=cri', Controller::addToUrl('do=page&amp;ref=bar', false));
        $this->assertSame('?do=page&amp;id=2&amp;act=edit&amp;ref=cri', Controller::addToUrl('act=edit&id=2', false));
        $this->assertSame('?do=page&amp;id=2&amp;act=edit&amp;ref=cri', Controller::addToUrl('act=edit&amp;id=2', false));
        $this->assertSame('?do=page&amp;id=4&amp;act=edit&amp;foo=%2B&amp;bar=%20&amp;ref=cri', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20', false));
        $this->assertSame('?do=page&amp;key=foo&amp;ref=cri', Controller::addToUrl('key=foo', true, ['id']));
    }

    /**
     * @dataProvider pageStatusIconProvider
     */
    public function testPageStatusIcon(PageModel $pageModel, string $expected): void
    {
        $this->assertSame($expected, Controller::getPageStatusIcon($pageModel));
        $this->assertFileExists(__DIR__.'/../../src/Resources/contao/themes/flexible/icons/'.$expected);
    }

    public function pageStatusIconProvider(): \Generator
    {
        yield 'Published' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => '',
                'protected' => '',
                'start' => '',
                'stop' => '',
                'published' => '1',
            ]),
            'regular.svg',
        ];

        yield 'Unpublished' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => '',
                'protected' => '',
                'start' => '',
                'stop' => '',
                'published' => '',
            ]),
            'regular_1.svg',
        ];

        yield 'Hidden in menu' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => '1',
                'protected' => '',
                'start' => '',
                'stop' => '',
                'published' => '1',
            ]),
            'regular_2.svg',
        ];

        yield 'Unpublished and hidden from menu' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => '1',
                'protected' => '',
                'start' => '',
                'stop' => '',
                'published' => '',
            ]),
            'regular_3.svg',
        ];

        yield 'Protected' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => '',
                'protected' => '1',
                'start' => '',
                'stop' => '',
                'published' => '1',
            ]),
            'regular_4.svg',
        ];

        yield 'Unpublished and protected' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => '',
                'protected' => '1',
                'start' => '',
                'stop' => '',
                'published' => '',
            ]),
            'regular_5.svg',
        ];

        yield 'Unpublished and protected and hidden from menu' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => '1',
                'protected' => '1',
                'start' => '',
                'stop' => '',
                'published' => '',
            ]),
            'regular_7.svg',
        ];

        yield 'Unpublished by stop date' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => '',
                'protected' => '',
                'start' => '',
                'stop' => '100',
                'published' => '1',
            ]),
            'regular_1.svg',
        ];

        yield 'Unpublished by start date' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => '',
                'protected' => '',
                'start' => PHP_INT_MAX,
                'stop' => '',
                'published' => '1',
            ]),
            'regular_1.svg',
        ];

        yield 'Root page' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'root',
                'hide' => '',
                'protected' => '',
                'start' => '',
                'stop' => '',
                'published' => '1',
            ]),
            'root.svg',
        ];

        yield 'Unpublished root page' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'root',
                'hide' => '',
                'protected' => '',
                'start' => '',
                'stop' => '',
                'published' => '',
            ]),
            'root_1.svg',
        ];

        yield 'Hidden root page' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'root',
                'hide' => '1',
                'protected' => '',
                'start' => '',
                'stop' => '',
                'published' => '1',
            ]),
            'root.svg',
        ];

        yield 'Protected root page' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'root',
                'hide' => '',
                'protected' => '1',
                'start' => '',
                'stop' => '',
                'published' => '1',
            ]),
            'root.svg',
        ];

        yield 'Root in maintenance mode' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'root',
                'hide' => '',
                'protected' => '',
                'maintenanceMode' => '1',
                'start' => '',
                'stop' => '',
                'published' => '1',
            ]),
            'root_2.svg',
        ];

        yield 'Unpublished root in maintenance mode' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'root',
                'hide' => '',
                'protected' => '',
                'maintenanceMode' => '1',
                'start' => '',
                'stop' => '',
                'published' => '',
            ]),
            'root_1.svg',
        ];
    }

    /**
     * @dataProvider redirectProvider
     *
     * @group legacy
     */
    public function testReplacesOldBePathsInRedirect(string $location, array $routes, string $expected): void
    {
        if (\count($routes)) {
            $this->expectDeprecation('Since contao/core-bundle 4.0: Using old backend paths has been deprecated %s.');
        }

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->exactly(\count($routes)))
            ->method('generate')
            ->withConsecutive(...array_map(static fn ($route) => [$route], $routes))
            ->willReturnOnConsecutiveCalls(...array_map(static fn ($route) => '/'.$route, $routes))
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('router', $router);

        $container->set('request_stack', $stack = new RequestStack());
        $stack->push(new Request());

        System::setContainer($container);

        try {
            Controller::redirect($location);
        } catch (RedirectResponseException $exception) {
            /** @var RedirectResponse $response */
            $response = $exception->getResponse();

            $this->assertInstanceOf(RedirectResponse::class, $response);
            $this->assertSame($expected, $response->getTargetUrl());
        }

        Controller::resetControllerCache();
    }

    public function redirectProvider(): \Generator
    {
        yield 'Never calls the router without old backend path' => [
            'https://example.com',
            [],
            'https://example.com',
        ];

        yield 'Replaces multiple paths (not really expected)' => [
            'https://example.com/contao/main.php?contao/password.php=foo',
            ['contao_backend', 'contao_backend_password'],
            'https://example.com/contao_backend?contao_backend_password=foo',
        ];

        $pathMap = [
            'contao/confirm.php' => 'contao_backend_confirm',
            'contao/help.php' => 'contao_backend_help',
            'contao/index.php' => 'contao_backend_login',
            'contao/main.php' => 'contao_backend',
            'contao/password.php' => 'contao_backend_password',
            'contao/popup.php' => 'contao_backend_popup',
            'contao/preview.php' => 'contao_backend_preview',
        ];

        foreach ($pathMap as $old => $new) {
            yield 'Replaces '.$old.' with '.$new.' route' => [
                "https://example.com/$old?foo=bar",
                [$new],
                "https://example.com/$new?foo=bar",
            ];
        }
    }

    /**
     * @group legacy
     */
    public function testCachesOldBackendPaths(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.0: Using old backend paths has been deprecated %s.');

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->exactly(2))
            ->method('generate')
            ->withConsecutive(['contao_backend'], ['contao_backend_password'])
            ->willReturn('/contao', '/contao/password')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('router', $router);
        System::setContainer($container);

        Environment::reset();
        Environment::set('path', '');
        Environment::set('base', '');

        $ref = new \ReflectionClass(Controller::class);
        $method = $ref->getMethod('replaceOldBePaths');

        $this->assertSame(
            $method->invoke(null, 'This is a template with link to <a href="/contao/main.php">backend main</a> and <a href="/contao/main.php?do=articles">articles</a>'),
            'This is a template with link to <a href="/contao">backend main</a> and <a href="/contao?do=articles">articles</a>'
        );

        $this->assertSame(
            $method->invoke(null, 'Link to <a href="/contao/main.php">backend main</a> and <a href="/contao/password.php?x=y">password</a>'),
            'Link to <a href="/contao">backend main</a> and <a href="/contao/password?x=y">password</a>'
        );

        Environment::reset();
        Controller::resetControllerCache();
    }
}
