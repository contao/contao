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

use Contao\Controller;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Environment;
use Contao\System;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class ControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Controller::resetControllerCache();
    }

    public function testReturnsTheTimeZones(): void
    {
        $timeZones = System::getTimeZones();

        $this->assertCount(9, $timeZones['General']);
        $this->assertCount(51, $timeZones['Africa']);
        $this->assertCount(140, $timeZones['America']);
        $this->assertCount(10, $timeZones['Antarctica']);
        $this->assertCount(83, $timeZones['Asia']);
        $this->assertCount(11, $timeZones['Atlantic']);
        $this->assertCount(22, $timeZones['Australia']);
        $this->assertCount(4, $timeZones['Brazil']);
        $this->assertCount(9, $timeZones['Canada']);
        $this->assertCount(2, $timeZones['Chile']);
        $this->assertCount(53, $timeZones['Europe']);
        $this->assertCount(11, $timeZones['Indian']);
        $this->assertCount(4, $timeZones['Brazil']);
        $this->assertCount(3, $timeZones['Mexico']);
        $this->assertCount(40, $timeZones['Pacific']);
        $this->assertCount(13, $timeZones['United States']);
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

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('request_stack', $requestStack);

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

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('request_stack', $requestStack);

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
     * @dataProvider redirectProvider
     */
    public function testReplacesOldBePathsInRedirect(string $location, array $routes, string $expected): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->exactly(\count($routes)))
            ->method('generate')
            ->withConsecutive(
                ...array_map(
                    static function ($route) {
                        return [$route];
                    },
                    $routes
                )
            )
            ->willReturnOnConsecutiveCalls(
                ...array_map(
                    static function ($route) {
                        return '/'.$route;
                    },
                    $routes
                )
            )
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('router', $router);
        System::setContainer($container);

        Environment::reset();
        Environment::set('path', '');
        Environment::set('base', '');

        try {
            Controller::redirect($location);
        } catch (RedirectResponseException $exception) {
            /** @var RedirectResponse $response */
            $response = $exception->getResponse();

            $this->assertInstanceOf(RedirectResponse::class, $response);
            $this->assertSame($expected, $response->getTargetUrl());
        }
    }

    public function redirectProvider(): \Generator
    {
        yield 'Never calls the router without old backend path' => [
            'https://example.com',
            [],
            'https://example.com',
        ];

        yield 'Replaces multiple paths (not really expected)' => [
            'https://example.com/contao/main.php?contao/file.php=foo',
            ['contao_backend', 'contao_backend_file'],
            'https://example.com/contao_backend?contao_backend_file=foo',
        ];

        $pathMap = [
            'contao/confirm.php' => 'contao_backend_confirm',
            'contao/file.php' => 'contao_backend_file',
            'contao/help.php' => 'contao_backend_help',
            'contao/index.php' => 'contao_backend_login',
            'contao/main.php' => 'contao_backend',
            'contao/page.php' => 'contao_backend_page',
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

    public function testCachesOldBackendPaths(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->exactly(2))
            ->method('generate')
            ->withConsecutive(['contao_backend'], ['contao_backend_file'])
            ->willReturn('/contao', '/contao/file')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('router', $router);
        System::setContainer($container);

        Environment::reset();
        Environment::set('path', '');
        Environment::set('base', '');

        $ref = new \ReflectionClass(Controller::class);
        $method = $ref->getMethod('replaceOldBePaths');
        $method->setAccessible(true);

        $this->assertSame(
            $method->invoke(null, 'This is a template with link to <a href="/contao/main.php">backend main</a> and <a href="/contao/main.php?do=articles">articles</a>'),
            'This is a template with link to <a href="/contao">backend main</a> and <a href="/contao?do=articles">articles</a>'
        );

        $this->assertSame(
            $method->invoke(null, 'Link to <a href="/contao/main.php">backend main</a> and <a href="/contao/file.php?x=y">files</a>'),
            'Link to <a href="/contao">backend main</a> and <a href="/contao/file?x=y">files</a>'
        );
    }
}
