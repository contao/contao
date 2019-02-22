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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

class WidgetTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $container = new ContainerBuilder();
        $container->set('request_stack', new RequestStack());

        System::setContainer($container);
    }

    /**
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
        /** @var Widget|MockObject $widget */
        $widget = $this
            ->getMockBuilder(Widget::class)
            ->disableOriginalConstructor()
            ->setMethods(['validator'])
            ->getMockForAbstractClass()
        ;

        $widget
            ->expects($this->exactly(3))
            ->method('validator')
            ->withAnyParameters()
            ->willReturnArgument(0)
        ;

        $widget
            ->setInputCallback(
                function (): string {
                    return 'foobar';
                }
            )
            ->validate()
        ;

        $this->assertSame('foobar', $widget->value);

        $widget
            ->setInputCallback(
                function () {
                    return null;
                }
            )
            ->validate()
        ;

        $this->assertNull($widget->value);

        $widget
            ->setInputCallback()
            ->validate() // getPost() should be called once here
        ;
    }
}
