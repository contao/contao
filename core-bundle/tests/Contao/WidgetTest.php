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

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Input;
use Contao\System;
use Contao\TextField;
use Contao\Widget;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class WidgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new ContainerBuilder();
        $container->set('request_stack', new RequestStack());
        $container->set('contao.routing.scope_matcher', $this->createMock(ScopeMatcher::class));
        $container->setParameter('kernel.charset', 'UTF-8');
        $container->setParameter('contao.image.valid_extensions', ['jpg', 'gif', 'png']);

        System::setContainer($container);
    }

    protected function tearDown(): void
    {
        $this->resetStaticProperties([Input::class, System::class]);

        parent::tearDown();
    }

    /**
     * @param array<string>|string $value
     *
     * @dataProvider postProvider
     */
    public function testReadsThePostData(string $key, string $input, array|string $value, string $expected = null): void
    {
        // Prevent "undefined index" errors
        $errorReporting = error_reporting();
        error_reporting($errorReporting & ~E_NOTICE);

        $widget = $this->createMock(Widget::class);

        $class = new \ReflectionClass(Widget::class);
        $method = $class->getMethod('getPost');

        System::getContainer()->set('request_stack', $stack = new RequestStack());
        $stack->push(new Request([], [$input => $value]));

        $this->assertSame($expected, $method->invoke($widget, $key));

        // Restore the error reporting level
        error_reporting($errorReporting);
        $_POST = [];
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
        System::getContainer()->set('request_stack', $stack = new RequestStack());
        $stack->push(new Request());

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

        if (isset($parameters[2])) {
            $widget = (new \ReflectionClass(TextField::class))->newInstanceWithoutConstructor();
            $widget->addAttributes($attrs);
            $this->assertSame($parameters[2], $widget->value);
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

        yield [
            [[], 'name', '&amp;,&lt;,&gt;,&nbsp;,&shy;'],
            [
                'value' => '&amp;,&lt;,&gt;,&nbsp;,&shy;',
            ],
        ];

        yield [
            [
                ['eval' => ['basicEntities' => true]],
                'name',
                '&amp;,&lt;,&gt;,&nbsp;,&shy;',
            ],
            [
                'basicEntities' => true,
                'value' => '[&],[lt],[gt],[nbsp],[-]',
            ],
        ];
    }
}
