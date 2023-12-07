<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Dca\Schema;

use Contao\Backend;
use Contao\CoreBundle\Dca\Data;
use Contao\CoreBundle\Dca\Schema\Operation;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;

class OperationTest extends TestCase
{
    /**
     * @param string|array|null $label
     *
     * @dataProvider labelProvider
     */
    public function testParseLabel($label, string $id, string $expected, string $name = 'foo'): void
    {
        $operation = new Operation($name, new Data(['label' => $label]));

        $this->assertSame($expected, $operation->parseLabel($id));
    }

    public function labelProvider(): array
    {
        return [
            'string' => ['Foo', '1', 'Foo'],
            'string with placeholder' => ['Foo %s', '1', 'Foo 1'],
            'array' => [['Foo', 'bar'], '1', 'Foo'],
            'array with placeholder' => [['Foo %s', 'bar'], '1', 'Foo 1'],
            'empty string' => ['', '1', 'operation_foo', 'operation_foo'],
            'empty array' => [[], '1', 'operation_foo', 'operation_foo'],
            'null' => [null, '1', 'operation_foo', 'operation_foo'],
        ];
    }

    /**
     * @param string|array|null $label
     *
     * @dataProvider titleProvider
     */
    public function testParseTitle($label, string $id, string $expected, string $name = 'foo'): void
    {
        $operation = new Operation($name, new Data(['label' => $label]));

        $this->assertSame($expected, $operation->parseTitle($id));
    }

    public function titleProvider(): array
    {
        return [
            'label string' => ['Foo', '1', 'Foo'],
            'label string with placeholder' => ['Foo %s', '1', 'Foo 1'],
            'array with title' => [['Foo', 'bar'], '1', 'bar'],
            'array without title' => [['Foo'], '1', 'Foo'],
            'array with placeholder' => [['Foo %s', 'bar %s'], '1', 'bar 1'],
            'empty string' => [['', ''], '1', 'operation_foo', 'operation_foo'],
            'empty array' => [[], '1', 'operation_foo', 'operation_foo'],
            'null' => [null, '1', 'operation_foo', 'operation_foo'],
        ];
    }

    /**
     * @dataProvider attributesProvider
     */
    public function testParseAttributes(array $data, string $id, string $expected, string $name = 'foo'): void
    {
        $operation = new Operation($name, new Data($data));

        $this->assertSame($expected, $operation->parseAttributes($id));
    }

    public function attributesProvider(): array
    {
        return [
            'no attributes' => [[], '1', ' class="foo"'],
            'empty attributes' => [['attributes' => ''], '1', ' class="foo"'],
            'custom attributes' => [['attributes' => 'data-foo="bar"'], '1', ' class="foo" data-foo="bar"'],
            'attributes with placeholder' => [['attributes' => 'data-foo="bar %s"'], '1', ' class="foo" data-foo="bar 1"'],
            'merge class value' => [['attributes' => 'data-foo="bar"', 'class' => 'class-a class-b'], '1', ' class="foo class-a class-b" data-foo="bar"'],
            'merge class value and attribute' => [['attributes' => 'data-foo="bar" class="class-c"', 'class' => 'class-a class-b'], '1', ' data-foo="bar" class="foo class-a class-b class-c"'],
            'trim whitespace' => [['attributes' => '   data-foo="bar"  '], '1', ' class="foo" data-foo="bar"'],
        ];
    }

    public function testParseHrefFromRouteAttribute(): void
    {
        $data = [
            'route' => 'contao.custom',
            'href' => 'foo',
        ];
        $params = [
            'id' => 1,
        ];

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao.custom', $params)
            ->willReturn('contao/custom?id=1')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->with('router')
            ->willReturn($router)
        ;

        $operation = new Operation('foo', new Data($data));
        $operation->setLocator($container);

        $this->assertSame('contao/custom?id=1', $operation->parseHref($params));
    }

    /**
     * @dataProvider hrefProvider
     */
    public function testParseHrefFromHrefAttribute(string $href, array $params, string $expected): void
    {
        $data = [
            'href' => $href,
        ];

        $backendAdapter = $this->mockAdapter(['addToUrl']);
        $backendAdapter
            ->expects($this->once())
            ->method('addToUrl')
            ->with($expected)
            ->willReturn('contao?do=foo&'.$expected)
        ;

        $framework = $this->mockContaoFramework([
            Backend::class => $backendAdapter,
        ]);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->with('contao.framework')
            ->willReturn($framework)
        ;

        $operation = new Operation('foo', new Data($data));
        $operation->setLocator($container);

        $this->assertSame('contao?do=foo&'.$expected, $operation->parseHref($params));
    }

    public function hrefProvider(): array
    {
        return [
            'with ID' => ['act=foo', ['id' => 1], 'act=foo&amp;id=1'],
            'with ID and popup' => ['act=bar', ['id' => 2, 'popup' => true], 'act=bar&amp;id=2&amp;popup=1'],
        ];
    }

    public function testParseIcon(): void
    {
        $data = [
            'label' => 'foo %s',
            'icon' => 'bar.svg',
        ];

        $imageAdapter = $this->mockAdapter(['getHtml']);
        $imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('bar.svg', 'foo 2')
            ->willReturn('<img />')
        ;

        $backendAdapter = $this->mockAdapter(['addToUrl']);
        $backendAdapter
            ->expects($this->once())
            ->method('addToUrl')
            ->willReturn('contao?do=foo')
        ;

        $framework = $this->mockContaoFramework([
            Image::class => $imageAdapter,
            Backend::class => $backendAdapter,
        ]);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->with('contao.framework')
            ->willReturn($framework)
        ;

        $operation = new Operation('foo', new Data($data));
        $operation->setLocator($container);

        $this->assertSame('<img />', $operation->parseIcon('2'));
    }
}
