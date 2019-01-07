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

    public function testDoesNotInitializeFrameworkWithoutPageModel(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $enhancer = new InputEnhancer($framework, false);
        $enhancer->enhance([], $this->createMock(Request::class));
    }

    /**
     * @dataProvider getLocales
     */
    public function testAddsLocaleToInputIfEnabled(bool $prependLocale, string $locale): void
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
        $enhancer->enhance($defaults, $this->createMock(Request::class));
    }

    /**
     * @return (bool|string)[][]
     */
    public function getLocales(): array
    {
        return [
            [false, 'en'],
            [false, 'de'],
            [true, 'de'],
            [true, 'en'],
        ];
    }

    public function testDoesNotAddInputLanguageIfLocaleIsMissing(): void
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
        $enhancer->enhance($defaults, $this->createMock(Request::class));
    }

    /**
     * @dataProvider getParameters
     */
    public function testAddsParametersToInput(string $parameters, bool $useAutoItem, array ...$setters): void
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
        $enhancer->enhance($defaults, $this->createMock(Request::class));
    }

    /**
     * @return (bool|string|string[])[][]
     */
    public function getParameters(): array
    {
        return [
            ['/foo/bar', false, ['foo', 'bar']],
            ['/foo/bar/bar/baz', false, ['foo', 'bar'], ['bar', 'baz']],
            ['/foo/bar/baz', true, ['auto_item', 'foo'], ['bar', 'baz']],
            ['/foo/bar//baz', false, ['foo', 'bar']],
            ['/foo/bar/baz', false, ['foo', 'bar'], ['baz', '']],
            ['/f%20o/bar', false, ['f o', 'bar']],
            ['/foo/ba%20r', false, ['foo', 'ba r']],
        ];
    }

    public function testThrowsExceptionIfParameterIsInQuery(): void
    {
        $framework = $this->mockContaoFramework();

        $defaults = [
            'pageModel' => $this->createMock(PageModel::class),
            'parameters' => '/foo/bar',
        ];

        $_GET = ['foo' => 'baz'];

        $enhancer = new InputEnhancer($framework, false);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Duplicate parameter "foo" in path');

        $enhancer->enhance($defaults, $this->createMock(Request::class));
    }

    public function testThrowsExceptionOnDuplicateAutoItem(): void
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

        $enhancer->enhance($defaults, $this->createMock(Request::class));
    }
}
