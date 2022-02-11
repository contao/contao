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

use Contao\Input;
use Contao\System;
use Contao\Widget;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

class WidgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new ContainerBuilder();
        $container->set('request_stack', new RequestStack());
        $container->setParameter('kernel.charset', 'UTF-8');
        $container->setParameter('contao.image.valid_extensions', ['jpg', 'gif', 'png']);

        System::setContainer($container);
    }

    /**
     * @param array<string>|string $value
     *
     * @dataProvider postProvider
     */
    public function testReadsThePostData(string $key, string $input, $value, string $expected = null): void
    {
        // Prevent "undefined index" errors
        $errorReporting = error_reporting();
        error_reporting($errorReporting & ~E_NOTICE);

        $widget = $this->createMock(Widget::class);

        $class = new \ReflectionClass(Widget::class);
        $method = $class->getMethod('getPost');
        $method->setAccessible(true);

        $_GET = [];
        $_POST = [$input => $value];
        Input::resetCache();
        Input::initialize();

        $this->assertSame($expected, $method->invoke($widget, $key));

        // Restore the error reporting level
        error_reporting($errorReporting);
    }

    public function postProvider(): \Generator
    {
        yield [
            'foo',
            'foo',
            'bar',
            'bar',
        ];

        yield [
            'foo[0]',
            'foo',
            ['bar'],
            'bar',
        ];

        yield [
            'foo[k1][k2][k3]',
            'foo',
            ['k1' => ['k2' => ['k3' => 'bar']]],
            'bar',
        ];

        yield [
            'foo[0]',
            'foo',
            ['k1' => 'bar'],
            null,
        ];

        yield [
            'foo[k1][0]',
            'foo',
            ['k1' => 'bar'],
            'bar',
        ];

        yield [
            'foo',
            'nofoo',
            'bar',
            null,
        ];

        yield [
            '',
            'foo',
            'bar',
            null,
        ];

        yield [
            '',
            '',
            'bar',
            'bar',
        ];

        yield [
            '[0]',
            '',
            ['bar'],
            'bar',
        ];
    }

    public function testValidatesThePostData(): void
    {
        $widget = $this
            ->getMockBuilder(Widget::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validator'])
            ->getMockForAbstractClass()
        ;

        $widget
            ->expects($this->exactly(3))
            ->method('validator')
            ->withAnyParameters()
            ->willReturnArgument(0)
        ;

        $widget
            ->setInputCallback(static fn (): string => 'foobar')
            ->validate()
        ;

        $this->assertSame('foobar', $widget->value);

        $widget
            ->setInputCallback(static fn () => null)
            ->validate()
        ;

        $this->assertNull($widget->value);

        $widget
            ->setInputCallback()
            ->validate() // getPost() should be called once here
;
    }

    /**
     * @dataProvider getAttributesFromDca
     */
    public function testGetsAttributesFromDca(array $parameters, array $expected): void
    {
        $attrs = Widget::getAttributesFromDca(...$parameters);

        foreach ($expected as $key => $value) {
            $this->assertSame($value, $attrs[$key]);
        }
    }

    public function getAttributesFromDca(): \Generator
    {
        yield [
            [[], 'foo'],
            [
                'name' => 'foo',
                'id' => 'foo',
            ],
        ];

        yield [
            [['eval' => ['foo' => '%kernel.charset%']], 'name'],
            [
                'foo' => 'UTF-8',
            ],
        ];

        yield [
            [['eval' => ['foo' => 'bar%kernel.charset%baz']], 'name'],
            [
                'foo' => 'barUTF-8baz',
            ],
        ];

        yield [
            [['eval' => ['foo' => '%%b%%ar%kernel.charset%ba%%z']], 'name'],
            [
                'foo' => '%b%arUTF-8ba%z',
            ],
        ];

        yield [
            [['eval' => ['foo' => '%%% xxx %%%']], 'name'],
            [
                'foo' => '%%% xxx %%%',
            ],
        ];

        yield [
            [['eval' => ['foo' => '50% discount 20% VAT']], 'name'],
            [
                'foo' => '50% discount 20% VAT',
            ],
        ];

        yield [
            [['eval' => ['extensions' => '%contao.image.valid_extensions%']], 'name'],
            [
                'extensions' => ['jpg', 'gif', 'png'],
            ],
        ];
    }
}
