<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\Enhancer;

use Contao\Config;
use Contao\CoreBundle\Routing\Enhancer\InputEnhancer;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Input;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class InputEnhancerTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        unset($_GET, $GLOBALS['TL_AUTO_ITEM']);
    }

    public function testReturnsTheDefaultsIfThereIsNoPageModel(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $enhancer = new InputEnhancer($framework, false);
        $enhancer->enhance([], Request::create('/'));
    }

    /**
     * @dataProvider getLocales
     */
    public function testAddsTheLocaleIfEnabled(bool $prependLocale, string $locale): void
    {
        $input = $this->mockAdapter(['setGet']);
        $input
            ->expects($prependLocale ? $this->once() : $this->never())
            ->method('setGet')
            ->with('language', $locale)
        ;

        $framework = $this->mockContaoFramework([Input::class => $input]);

        $defaults = [
            'pageModel' => $this->createMock(PageModel::class),
            '_locale' => $locale,
        ];

        $enhancer = new InputEnhancer($framework, $prependLocale);
        $enhancer->enhance($defaults, Request::create('/'));
    }

    public function getLocales(): \Generator
    {
        yield [false, 'en'];
        yield [false, 'de'];
        yield [true, 'de'];
        yield [true, 'en'];
    }

    public function testDoesNotAddTheLocaleIfItIsNotPresent(): void
    {
        $input = $this->mockAdapter(['setGet']);
        $input
            ->expects($this->never())
            ->method('setGet')
        ;

        $framework = $this->mockContaoFramework([Input::class => $input]);

        $defaults = [
            'pageModel' => $this->createMock(PageModel::class),
        ];

        $enhancer = new InputEnhancer($framework, true);
        $enhancer->enhance($defaults, Request::create('/'));
    }

    /**
     * @dataProvider getParameters
     */
    public function testAddsParameters(string $parameters, bool $useAutoItem, array ...$setters): void
    {
        // Input::setGet must always be called with $blnAddUnused=true
        array_walk(
            $setters,
            function (array &$set): void {
                $set[2] = true;
            }
        );

        $input = $this->mockAdapter(['setGet']);
        $input
            ->expects($this->exactly(\count($setters)))
            ->method('setGet')
            ->withConsecutive(...$setters)
        ;

        $adapters = [
            Input::class => $input,
            Config::class => $this->mockConfiguredAdapter(['get' => $useAutoItem]),
        ];

        $framework = $this->mockContaoFramework($adapters);

        $defaults = [
            'pageModel' => $this->createMock(PageModel::class),
            'parameters' => $parameters,
        ];

        $enhancer = new InputEnhancer($framework, false);
        $enhancer->enhance($defaults, Request::create('/'));
    }

    public function getParameters(): \Generator
    {
        yield ['/foo/bar', false, ['foo', 'bar']];
        yield ['/foo/bar/bar/baz', false, ['foo', 'bar'], ['bar', 'baz']];
        yield ['/foo/bar/baz', true, ['auto_item', 'foo'], ['bar', 'baz']];
        yield ['/f%20o/bar', false, ['f%20o', 'bar']];
        yield ['/foo/ba%20r', false, ['foo', 'ba%20r']];
    }

    public function testThrowsAnExceptionUponDuplicateParameters(): void
    {
        $input = $this->mockAdapter(['setGet']);
        $input
            ->expects($this->never())
            ->method('setGet')
        ;

        $framework = $this->mockContaoFramework([Input::class => $input]);

        $defaults = [
            'pageModel' => $this->createMock(PageModel::class),
            'parameters' => '/foo/bar',
        ];

        $_GET = ['foo' => 'baz'];

        $enhancer = new InputEnhancer($framework, false);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Duplicate parameter "foo" in path');

        $enhancer->enhance($defaults, Request::create('/'));
    }

    public function testThrowsAnExceptionIfAnAutoItemKeywordIsPresent(): void
    {
        $input = $this->mockAdapter(['setGet']);
        $input
            ->expects($this->once())
            ->method('setGet')
            ->with('auto_item', 'foo')
        ;

        $framework = $this->mockContaoFramework([Input::class => $input]);

        $defaults = [
            'pageModel' => $this->createMock(PageModel::class),
            'parameters' => '/foo/bar/bar',
        ];

        $GLOBALS['TL_AUTO_ITEM'] = ['bar'];

        $enhancer = new InputEnhancer($framework, false);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('"bar" is an auto_item keyword (duplicate content)');

        $enhancer->enhance($defaults, Request::create('/'));
    }

    public function testThrowsAnExceptionIfTheNumberOfArgumentsIsInvalid(): void
    {
        $input = $this->mockAdapter(['setGet']);
        $input
            ->expects($this->never())
            ->method('setGet')
        ;

        $adapters = [
            Input::class => $input,
            Config::class => $this->mockConfiguredAdapter(['get' => false]),
        ];

        $framework = $this->mockContaoFramework($adapters);

        $defaults = [
            'pageModel' => $this->createMock(PageModel::class),
            'parameters' => '/foo/bar/baz',
        ];

        $enhancer = new InputEnhancer($framework, false);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Invalid number of arguments');

        $enhancer->enhance($defaults, Request::create('/'));
    }

    public function testThrowsAnExceptionIfAFragmentKeyIsEmpty(): void
    {
        $input = $this->mockAdapter(['setGet']);
        $input
            ->expects($this->once())
            ->method('setGet')
            ->with('foo', 'bar')
        ;

        $adapters = [
            Input::class => $input,
            Config::class => $this->mockConfiguredAdapter(['get' => false]),
        ];

        $framework = $this->mockContaoFramework($adapters);

        $defaults = [
            'pageModel' => $this->createMock(PageModel::class),
            'parameters' => '/foo/bar//baz',
        ];

        $enhancer = new InputEnhancer($framework, false);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Empty fragment key in path');

        $enhancer->enhance($defaults, Request::create('/'));
    }
}
