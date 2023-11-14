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
use Contao\CoreBundle\Tests\TestCase;
use Contao\DcaExtractor;
use Contao\DcaLoader;
use Contao\Environment;
use Contao\PageModel;
use Contao\System;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\Request;

class ControllerTest extends TestCase
{
    use ExpectDeprecationTrait;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        Controller::resetControllerCache();
    }

    #[\Override]
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_MIME']);

        $this->resetStaticProperties([
            DcaExtractor::class,
            DcaLoader::class,
            System::class,
            Config::class,
            Environment::class,
            Controller::class,
        ]);

        parent::tearDown();
    }

    public function testAddToUrlWithoutQueryString(): void
    {
        $request = new Request();
        $request->attributes->set('_contao_referer_id', 'cri');

        $container = $this->getContainerWithContaoConfiguration();
        $container->get('request_stack')->push($request);

        System::setContainer($container);

        $this->assertSame('/', Controller::addToUrl(''));
        $this->assertSame('/?do=page&amp;ref=cri', Controller::addToUrl('do=page'));
        $this->assertSame('/?do=page&amp;rt=foo&amp;ref=cri', Controller::addToUrl('do=page&amp;rt=foo'));
        $this->assertSame('/?do=page&amp;ref=cri', Controller::addToUrl('do=page&amp;ref=bar'));
        $this->assertSame('/?act=edit&amp;id=2&amp;ref=cri', Controller::addToUrl('act=edit&id=2'));
        $this->assertSame('/?act=edit&amp;id=2&amp;ref=cri', Controller::addToUrl('act=edit&amp;id=2'));
        $this->assertSame('/?act=edit&amp;foo=%2B&amp;bar=%20&amp;ref=cri', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20'));

        $this->assertSame('/', Controller::addToUrl('', false));
        $this->assertSame('/?do=page', Controller::addToUrl('do=page', false));
        $this->assertSame('/?do=page&amp;rt=foo', Controller::addToUrl('do=page&amp;rt=foo', false));
        $this->assertSame('/?do=page&amp;ref=bar', Controller::addToUrl('do=page&amp;ref=bar', false));
        $this->assertSame('/?act=edit&amp;id=2', Controller::addToUrl('act=edit&id=2', false));
        $this->assertSame('/?act=edit&amp;id=2', Controller::addToUrl('act=edit&amp;id=2', false));
        $this->assertSame('/?act=edit&amp;foo=%2B&amp;bar=%20', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20', false));

        $request->query->set('ref', 'ref');

        $this->assertSame('/?ref=cri', Controller::addToUrl('', false));
        $this->assertSame('/?do=page&amp;ref=cri', Controller::addToUrl('do=page', false));
        $this->assertSame('/?do=page&amp;rt=foo&amp;ref=cri', Controller::addToUrl('do=page&amp;rt=foo', false));
        $this->assertSame('/?do=page&amp;ref=cri', Controller::addToUrl('do=page&amp;ref=bar', false));
        $this->assertSame('/?act=edit&amp;id=2&amp;ref=cri', Controller::addToUrl('act=edit&id=2', false));
        $this->assertSame('/?act=edit&amp;id=2&amp;ref=cri', Controller::addToUrl('act=edit&amp;id=2', false));
        $this->assertSame('/?act=edit&amp;foo=%2B&amp;bar=%20&amp;ref=cri', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20', false));
    }

    public function testAddToUrlWithQueryString(): void
    {
        $request = new Request();
        $request->attributes->set('_contao_referer_id', 'cri');
        $request->server->set('QUERY_STRING', 'do=page&id=4');

        $container = $this->getContainerWithContaoConfiguration();
        $container->get('request_stack')->push($request);

        System::setContainer($container);

        $this->assertSame('/?do=page&amp;id=4', Controller::addToUrl(''));
        $this->assertSame('/?do=page&amp;id=4&amp;ref=cri', Controller::addToUrl('do=page'));
        $this->assertSame('/?do=page&amp;id=4&amp;rt=foo&amp;ref=cri', Controller::addToUrl('do=page&amp;rt=foo'));
        $this->assertSame('/?do=page&amp;id=4&amp;ref=cri', Controller::addToUrl('do=page&amp;ref=bar'));
        $this->assertSame('/?do=page&amp;id=2&amp;act=edit&amp;ref=cri', Controller::addToUrl('act=edit&id=2'));
        $this->assertSame('/?do=page&amp;id=2&amp;act=edit&amp;ref=cri', Controller::addToUrl('act=edit&amp;id=2'));
        $this->assertSame('/?do=page&amp;id=4&amp;act=edit&amp;foo=%2B&amp;bar=%20&amp;ref=cri', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20'));
        $this->assertSame('/?do=page&amp;key=foo&amp;ref=cri', Controller::addToUrl('key=foo', true, ['id']));

        $this->assertSame('/?do=page&amp;id=4', Controller::addToUrl('', false));
        $this->assertSame('/?do=page&amp;id=4', Controller::addToUrl('do=page', false));
        $this->assertSame('/?do=page&amp;id=4&amp;rt=foo', Controller::addToUrl('do=page&amp;rt=foo', false));
        $this->assertSame('/?do=page&amp;id=4&amp;ref=bar', Controller::addToUrl('do=page&amp;ref=bar', false));
        $this->assertSame('/?do=page&amp;id=2&amp;act=edit', Controller::addToUrl('act=edit&id=2', false));
        $this->assertSame('/?do=page&amp;id=2&amp;act=edit', Controller::addToUrl('act=edit&amp;id=2', false));
        $this->assertSame('/?do=page&amp;id=4&amp;act=edit&amp;foo=%2B&amp;bar=%20', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20', false));
        $this->assertSame('/?do=page&amp;key=foo', Controller::addToUrl('key=foo', false, ['id']));

        $request->query->set('ref', 'ref');

        $this->assertSame('/?do=page&amp;id=4&amp;ref=cri', Controller::addToUrl('', false));
        $this->assertSame('/?do=page&amp;id=4&amp;ref=cri', Controller::addToUrl('do=page', false));
        $this->assertSame('/?do=page&amp;id=4&amp;rt=foo&amp;ref=cri', Controller::addToUrl('do=page&amp;rt=foo', false));
        $this->assertSame('/?do=page&amp;id=4&amp;ref=cri', Controller::addToUrl('do=page&amp;ref=bar', false));
        $this->assertSame('/?do=page&amp;id=2&amp;act=edit&amp;ref=cri', Controller::addToUrl('act=edit&id=2', false));
        $this->assertSame('/?do=page&amp;id=2&amp;act=edit&amp;ref=cri', Controller::addToUrl('act=edit&amp;id=2', false));
        $this->assertSame('/?do=page&amp;id=4&amp;act=edit&amp;foo=%2B&amp;bar=%20&amp;ref=cri', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20', false));
        $this->assertSame('/?do=page&amp;key=foo&amp;ref=cri', Controller::addToUrl('key=foo', true, ['id']));
    }

    /**
     * @dataProvider pageStatusIconProvider
     */
    public function testPageStatusIcon(PageModel $pageModel, string $expected): void
    {
        $this->assertSame($expected, Controller::getPageStatusIcon($pageModel));
        $this->assertFileExists(__DIR__.'/../../contao/themes/flexible/icons/'.$expected);
    }

    public function pageStatusIconProvider(): \Generator
    {
        yield 'Published' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => false,
                'protected' => false,
                'start' => '',
                'stop' => '',
                'published' => true,
            ]),
            'regular.svg',
        ];

        yield 'Unpublished' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => false,
                'protected' => false,
                'start' => '',
                'stop' => '',
                'published' => false,
            ]),
            'regular_1.svg',
        ];

        yield 'Hidden in menu' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => true,
                'protected' => false,
                'start' => '',
                'stop' => '',
                'published' => true,
            ]),
            'regular_2.svg',
        ];

        yield 'Unpublished and hidden from menu' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => true,
                'protected' => false,
                'start' => '',
                'stop' => '',
                'published' => false,
            ]),
            'regular_3.svg',
        ];

        yield 'Protected' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => false,
                'protected' => true,
                'start' => '',
                'stop' => '',
                'published' => true,
            ]),
            'regular_4.svg',
        ];

        yield 'Unpublished and protected' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => false,
                'protected' => true,
                'start' => '',
                'stop' => '',
                'published' => false,
            ]),
            'regular_5.svg',
        ];

        yield 'Unpublished and protected and hidden from menu' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => true,
                'protected' => true,
                'start' => '',
                'stop' => '',
                'published' => false,
            ]),
            'regular_7.svg',
        ];

        yield 'Unpublished by stop date' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => false,
                'protected' => false,
                'start' => '',
                'stop' => '100',
                'published' => true,
            ]),
            'regular_1.svg',
        ];

        yield 'Unpublished by start date' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'regular',
                'hide' => false,
                'protected' => false,
                'start' => PHP_INT_MAX,
                'stop' => '',
                'published' => true,
            ]),
            'regular_1.svg',
        ];

        yield 'Root page' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'root',
                'hide' => false,
                'protected' => false,
                'start' => '',
                'stop' => '',
                'published' => true,
            ]),
            'root.svg',
        ];

        yield 'Unpublished root page' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'root',
                'hide' => false,
                'protected' => false,
                'start' => '',
                'stop' => '',
                'published' => false,
            ]),
            'root_1.svg',
        ];

        yield 'Hidden root page' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'root',
                'hide' => true,
                'protected' => false,
                'start' => '',
                'stop' => '',
                'published' => true,
            ]),
            'root.svg',
        ];

        yield 'Protected root page' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'root',
                'hide' => false,
                'protected' => true,
                'start' => '',
                'stop' => '',
                'published' => true,
            ]),
            'root.svg',
        ];

        yield 'Root in maintenance mode' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'root',
                'hide' => false,
                'protected' => false,
                'maintenanceMode' => true,
                'start' => '',
                'stop' => '',
                'published' => true,
            ]),
            'root_2.svg',
        ];

        yield 'Unpublished root in maintenance mode' => [
            $this->mockClassWithProperties(PageModel::class, [
                'type' => 'root',
                'hide' => false,
                'protected' => true,
                'maintenanceMode' => true,
                'start' => '',
                'stop' => '',
                'published' => false,
            ]),
            'root_1.svg',
        ];
    }
}
