<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Compat;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Compat\ArrayValueHolder;
use Contao\CoreBundle\Twig\Compat\InvokableValueHolder;
use Contao\CoreBundle\Twig\Compat\ObjectValueHolder;
use Contao\CoreBundle\Twig\Compat\ProxyFactory;
use Contao\CoreBundle\Twig\Compat\SafeHTMLValueHolderInterface;
use Contao\CoreBundle\Twig\Compat\ScalarValueHolder;

class ProxyFactoryTest extends TestCase
{
    /**
     * @dataProvider provideValuesThatShouldNotGetWrapped
     */
    public function testCreateValueHolderDoesNotWrapValue($value): void
    {
        $this->assertSame($value, ProxyFactory::createValueHolder($value));
    }

    public function provideValuesThatShouldNotGetWrapped(): \Generator
    {
        yield [true];

        yield [false];

        yield [null];

        yield [-123];

        yield [2.5];

        yield ['-123'];

        yield ['2.5'];

        yield [''];

        yield [[]];

        yield [new class() implements SafeHTMLValueHolderInterface {
        }];
    }

    public function testCreateValueHolderWrapsScalars(): void
    {
        $wrapped = ProxyFactory::createValueHolder('<i>foobar</i>');

        $this->assertInstanceOf(ScalarValueHolder::class, $wrapped);
        $this->assertSame('<i>foobar</i>', (string) $wrapped);
    }

    public function testCreateValueHolderWrapsArrays(): void
    {
        $wrapped = ProxyFactory::createValueHolder(['foo' => 1, 'bar' => '<i>foobar</i>']);

        $this->assertInstanceOf(ArrayValueHolder::class, $wrapped);

        $this->assertSame(1, $wrapped['foo']);

        $this->assertInstanceOf(ScalarValueHolder::class, $wrapped['bar']);
        $this->assertSame('<i>foobar</i>', (string) $wrapped['bar']);
    }

    /**
     * @dataProvider provideCallables
     */
    public function testCreateValueHolderWrapsCallables(callable $callable): void
    {
        $wrapped = ProxyFactory::createValueHolder($callable);

        $this->assertInstanceOf(InvokableValueHolder::class, $wrapped);
        $this->assertSame('<i>foo</i>', (string) $wrapped);

        $this->assertInstanceOf(ScalarValueHolder::class, $wrapped->invoke());
        $this->assertSame('<i>foo</i>', (string) $wrapped->invoke());

        $this->assertSame('<i>foobar</i>', (string) $wrapped->invoke('bar'));
    }

    public function provideCallables(): \Generator
    {
        yield 'array callable' => [
            [$this, 'dummyCallable'],
        ];

        yield 'anonymous function' => [
            static function ($string = '') {
                return "<i>foo$string</i>";
            },
        ];

        yield 'closure' => [
            \Closure::fromCallable([$this, 'dummyCallable']),
        ];
    }

    public function dummyCallable($string = ''): string
    {
        return "<i>foo$string</i>";
    }

    public function testCreateValueHolderWrapsObjects(): void
    {
        $object = new class() {
            /**
             * @var string
             */
            public $foobar = '<i>bar</i>';

            public function getFoo(): string
            {
                return '<p>foo</p>';
            }

            public function hasBar(): bool
            {
                return true;
            }
        };

        $wrapped = ProxyFactory::createValueHolder($object);

        $this->assertInstanceOf(ObjectValueHolder::class, $wrapped);

        $this->assertInstanceOf(ScalarValueHolder::class, $wrapped->foobar());
        $this->assertSame('<i>bar</i>', (string) $wrapped->foobar());

        $this->assertInstanceOf(ScalarValueHolder::class, $wrapped->getFoo());
        $this->assertInstanceOf(ScalarValueHolder::class, $wrapped->foo());
        $this->assertSame('<p>foo</p>', (string) $wrapped->getFoo());
        $this->assertSame('<p>foo</p>', (string) $wrapped->foo());

        $this->assertTrue($wrapped->hasBar());
        $this->assertTrue($wrapped->bar());
    }
}
