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
use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DcaExtractor;
use Contao\DcaLoader;
use Contao\Environment;
use Contao\ModuleModel;
use Contao\ModuleProxy;
use Contao\PageModel;
use Contao\System;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;

class ControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Controller::resetControllerCache();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_MIME'], $GLOBALS['FE_MOD']);

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

        $container = $this->getContainerWithContaoConfiguration();
        $container->get('request_stack')->push($request);

        System::setContainer($container);

        $this->assertSame('/', Controller::addToUrl(''));
        $this->assertSame('/?do=page', Controller::addToUrl('do=page'));
        $this->assertSame('/?do=page&amp;rt=foo', Controller::addToUrl('do=page&amp;rt=foo'));
        $this->assertSame('/?do=page', Controller::addToUrl('do=page'));
        $this->assertSame('/?act=edit&amp;id=2', Controller::addToUrl('act=edit&id=2'));
        $this->assertSame('/?act=edit&amp;id=2', Controller::addToUrl('act=edit&amp;id=2'));
        $this->assertSame('/?act=edit&amp;foo=%2B&amp;bar=%20', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20'));

        $this->assertSame('/', Controller::addToUrl('', false));
        $this->assertSame('/?do=page', Controller::addToUrl('do=page', false));
        $this->assertSame('/?do=page&amp;rt=foo', Controller::addToUrl('do=page&amp;rt=foo', false));
        $this->assertSame('/?do=page', Controller::addToUrl('do=page', false));
        $this->assertSame('/?act=edit&amp;id=2', Controller::addToUrl('act=edit&id=2', false));
        $this->assertSame('/?act=edit&amp;id=2', Controller::addToUrl('act=edit&amp;id=2', false));
        $this->assertSame('/?act=edit&amp;foo=%2B&amp;bar=%20', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20', false));

        $this->assertSame('/?do=page', Controller::addToUrl('do=page', false));
        $this->assertSame('/?do=page&amp;rt=foo', Controller::addToUrl('do=page&amp;rt=foo', false));
        $this->assertSame('/?do=page', Controller::addToUrl('do=page', false));
        $this->assertSame('/?act=edit&amp;id=2', Controller::addToUrl('act=edit&id=2', false));
        $this->assertSame('/?act=edit&amp;id=2', Controller::addToUrl('act=edit&amp;id=2', false));
        $this->assertSame('/?act=edit&amp;foo=%2B&amp;bar=%20', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20', false));
    }

    public function testAddToUrlWithQueryString(): void
    {
        $request = new Request();
        $request->server->set('QUERY_STRING', 'do=page&id=4');

        $container = $this->getContainerWithContaoConfiguration();
        $container->get('request_stack')->push($request);

        System::setContainer($container);

        $this->assertSame('/?do=page&amp;id=4', Controller::addToUrl(''));
        $this->assertSame('/?do=page&amp;id=4', Controller::addToUrl('do=page'));
        $this->assertSame('/?do=page&amp;id=4&amp;rt=foo', Controller::addToUrl('do=page&amp;rt=foo'));
        $this->assertSame('/?do=page&amp;id=4', Controller::addToUrl('do=page'));
        $this->assertSame('/?do=page&amp;id=2&amp;act=edit', Controller::addToUrl('act=edit&id=2'));
        $this->assertSame('/?do=page&amp;id=2&amp;act=edit', Controller::addToUrl('act=edit&amp;id=2'));
        $this->assertSame('/?do=page&amp;id=4&amp;act=edit&amp;foo=%2B&amp;bar=%20', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20'));
        $this->assertSame('/?do=page&amp;key=foo', Controller::addToUrl('key=foo', true, ['id']));

        $this->assertSame('/?do=page&amp;id=4', Controller::addToUrl('', false));
        $this->assertSame('/?do=page&amp;id=4', Controller::addToUrl('do=page', false));
        $this->assertSame('/?do=page&amp;id=4&amp;rt=foo', Controller::addToUrl('do=page&amp;rt=foo', false));
        $this->assertSame('/?do=page&amp;id=4', Controller::addToUrl('do=page', false));
        $this->assertSame('/?do=page&amp;id=2&amp;act=edit', Controller::addToUrl('act=edit&id=2', false));
        $this->assertSame('/?do=page&amp;id=2&amp;act=edit', Controller::addToUrl('act=edit&amp;id=2', false));
        $this->assertSame('/?do=page&amp;id=4&amp;act=edit&amp;foo=%2B&amp;bar=%20', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20', false));
        $this->assertSame('/?do=page&amp;key=foo', Controller::addToUrl('key=foo', false, ['id']));

        $this->assertSame('/?do=page&amp;id=4', Controller::addToUrl('', false));
        $this->assertSame('/?do=page&amp;id=4', Controller::addToUrl('do=page', false));
        $this->assertSame('/?do=page&amp;id=4&amp;rt=foo', Controller::addToUrl('do=page&amp;rt=foo', false));
        $this->assertSame('/?do=page&amp;id=4', Controller::addToUrl('do=page', false));
        $this->assertSame('/?do=page&amp;id=2&amp;act=edit', Controller::addToUrl('act=edit&id=2', false));
        $this->assertSame('/?do=page&amp;id=2&amp;act=edit', Controller::addToUrl('act=edit&amp;id=2', false));
        $this->assertSame('/?do=page&amp;id=4&amp;act=edit&amp;foo=%2B&amp;bar=%20', Controller::addToUrl('act=edit&amp;foo=%2B&amp;bar=%20', false));
        $this->assertSame('/?do=page&amp;key=foo', Controller::addToUrl('key=foo', true, ['id']));
    }

    #[DataProvider('pageStatusIconProvider')]
    public function testPageStatusIcon(array $pageModelData, string $expected): void
    {
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, $pageModelData);

        $this->assertSame($expected, Controller::getPageStatusIcon($pageModel));
        $this->assertFileExists(__DIR__.'/../../contao/themes/flexible/icons/'.$expected);
    }

    public static function pageStatusIconProvider(): iterable
    {
        yield 'Published' => [
            [
                'type' => 'regular',
                'hide' => false,
                'protected' => false,
                'start' => '',
                'stop' => '',
                'published' => true,
            ],
            'regular.svg',
        ];

        yield 'Unpublished' => [
            [
                'type' => 'regular',
                'hide' => false,
                'protected' => false,
                'start' => '',
                'stop' => '',
                'published' => false,
            ],
            'regular_1.svg',
        ];

        yield 'Hidden in menu' => [
            [
                'type' => 'regular',
                'hide' => true,
                'protected' => false,
                'start' => '',
                'stop' => '',
                'published' => true,
            ],
            'regular_2.svg',
        ];

        yield 'Unpublished and hidden from menu' => [
            [
                'type' => 'regular',
                'hide' => true,
                'protected' => false,
                'start' => '',
                'stop' => '',
                'published' => false,
            ],
            'regular_3.svg',
        ];

        yield 'Protected' => [
            [
                'type' => 'regular',
                'hide' => false,
                'protected' => true,
                'start' => '',
                'stop' => '',
                'published' => true,
            ],
            'regular_4.svg',
        ];

        yield 'Unpublished and protected' => [
            [
                'type' => 'regular',
                'hide' => false,
                'protected' => true,
                'start' => '',
                'stop' => '',
                'published' => false,
            ],
            'regular_5.svg',
        ];

        yield 'Unpublished and protected and hidden from menu' => [
            [
                'type' => 'regular',
                'hide' => true,
                'protected' => true,
                'start' => '',
                'stop' => '',
                'published' => false,
            ],
            'regular_7.svg',
        ];

        yield 'Unpublished by stop date' => [
            [
                'type' => 'regular',
                'hide' => false,
                'protected' => false,
                'start' => '',
                'stop' => '100',
                'published' => true,
            ],
            'regular_1.svg',
        ];

        yield 'Unpublished by start date' => [
            [
                'type' => 'regular',
                'hide' => false,
                'protected' => false,
                'start' => PHP_INT_MAX,
                'stop' => '',
                'published' => true,
            ],
            'regular_1.svg',
        ];

        yield 'Root page' => [
            [
                'type' => 'root',
                'hide' => false,
                'protected' => false,
                'start' => '',
                'stop' => '',
                'published' => true,
            ],
            'root.svg',
        ];

        yield 'Unpublished root page' => [
            [
                'type' => 'root',
                'hide' => false,
                'protected' => false,
                'start' => '',
                'stop' => '',
                'published' => false,
            ],
            'root_1.svg',
        ];

        yield 'Hidden root page' => [
            [
                'type' => 'root',
                'hide' => true,
                'protected' => false,
                'start' => '',
                'stop' => '',
                'published' => true,
            ],
            'root.svg',
        ];

        yield 'Protected root page' => [
            [
                'type' => 'root',
                'hide' => false,
                'protected' => true,
                'start' => '',
                'stop' => '',
                'published' => true,
            ],
            'root.svg',
        ];

        yield 'Root in maintenance mode' => [
            [
                'type' => 'root',
                'hide' => false,
                'protected' => false,
                'maintenanceMode' => true,
                'start' => '',
                'stop' => '',
                'published' => true,
            ],
            'root_2.svg',
        ];

        yield 'Unpublished root in maintenance mode' => [
            [
                'type' => 'root',
                'hide' => false,
                'protected' => true,
                'maintenanceMode' => true,
                'start' => '',
                'stop' => '',
                'published' => false,
            ],
            'root_1.svg',
        ];
    }

    public function testRendersFrontendModuleReference(): void
    {
        $GLOBALS['FE_MOD'] = ['miscellaneous' => ['test' => ModuleProxy::class]];

        $model = $this->createClassWithPropertiesStub(ModuleModel::class, ['id' => 42, 'type' => 'test']);
        $reference = new FrontendModuleReference($model, 'header', ['cssID' => ' id="example"'], true);

        $handler = $this->createMock(FragmentHandler::class);
        $handler
            ->expects($this->once())
            ->method('render')
            ->with($this->identicalTo($reference))
            ->willReturn('<p>module</p>')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->setParameter('kernel.debug', false);
        $container->set('contao.routing.page_finder', $this->createStub(PageFinder::class));
        $container->set('fragment.handler', $handler);
        System::setContainer($container);

        $this->assertSame('<p>module</p>', Controller::getFrontendModule($reference));
    }

    public function testRejectsColumnWhenRenderingFrontendModuleReference(): void
    {
        $reference = new FrontendModuleReference($this->createStub(ModuleModel::class));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Passing a column name or preloaded content elements is not supported when using a FrontendModuleReference.');

        Controller::getFrontendModule($reference, 'header');
    }
}
