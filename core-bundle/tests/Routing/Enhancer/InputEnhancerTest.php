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
    protected function setUp()
    {
        parent::setUp();

        unset($_GET, $GLOBALS['TL_AUTO_ITEM']);
    }

    public function testDoesNotInitializeFrameworkWithoutPageModel()
    {
        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $enhancer = new InputEnhancer($framework);
        $enhancer->enhance([], $this->createMock(Request::class));
    }

    /**
     * @dataProvider localeInUrlProvider
     */
    public function testAddsLocaleToInputIfEnabled(bool $addLanguageToUrl, string $locale)
    {
        $input = $this->mockAdapter(['setGet']);
        $input
            ->expects($addLanguageToUrl ? $this->once() : $this->never())
            ->method('setGet')
            ->with('language', $locale)
        ;

        $framework = $this->mockContaoFrameworkWithInputAndConfig($input, $addLanguageToUrl);

        $defaults = [
            'pageModel' => $this->createMock(PageModel::class),
            '_locale' => $locale,
        ];

        $enhancer = new InputEnhancer($framework);
        $enhancer->enhance($defaults, $this->createMock(Request::class));
    }

    public function localeInUrlProvider()
    {
        return [
            [false, 'en'],
            [false, 'de'],
            [true, 'de'],
            [true, 'en'],
        ];
    }

    public function testDoesNotAddInputLanguageIfLocaleIsMissing()
    {
        $input = $this->mockAdapter(['setGet']);
        $input
            ->expects($this->never())
            ->method('setGet')
        ;

        $framework = $this->mockContaoFrameworkWithInputAndConfig($input, true);

        $defaults = [
            'pageModel' => $this->createMock(PageModel::class),
        ];

        $enhancer = new InputEnhancer($framework);
        $enhancer->enhance($defaults, $this->createMock(Request::class));
    }

    /**
     * @dataProvider parameterProvider
     */
    public function testAddsParametersToInput(string $parameters, bool $useAutoItem, array ...$setters)
    {
        // Input::setGet must always be called with $blnAddUnused=true
        array_walk($setters, function (array &$set) {
            $set[2] = true;
        });

        $input = $this->mockAdapter(['setGet']);
        $input
            ->expects($this->exactly(count($setters)))
            ->method('setGet')
            ->withConsecutive(...$setters)
        ;

        $framework = $this->mockContaoFrameworkWithInputAndConfig($input, false, $useAutoItem);

        $defaults = [
            'pageModel' => $this->createMock(PageModel::class),
            'parameters' => $parameters,
        ];

        $enhancer = new InputEnhancer($framework);
        $enhancer->enhance($defaults, $this->createMock(Request::class));
    }

    public function parameterProvider()
    {
        return [
            ['/foo/bar', false, ['foo', 'bar']],
            ['/foo/bar/bar/baz', false, ['foo', 'bar'], ['bar', 'baz']],
            ['/foo/bar/baz', true, ['auto_item', 'foo'], ['bar', 'baz']],
            ['/foo/bar//baz', false, ['foo', 'bar']],
            ['/foo/bar/baz', false, ['foo', 'bar']],
            ['/f%20o/bar', false, ['f o', 'bar']],
        ];
    }

    public function testThrowsExceptionIfParameterIsInQuery()
    {
        $framework = $this->mockContaoFrameworkWithInputAndConfig();

        $defaults = [
            'pageModel' => $this->createMock(PageModel::class),
            'parameters' => '/foo/bar',
        ];

        $_GET = ['foo' => 'baz'];

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Duplicate parameter "foo" in path.');

        $enhancer = new InputEnhancer($framework);
        $enhancer->enhance($defaults, $this->createMock(Request::class));
    }

    public function testThrowsExceptionOnDuplicateAutoItem()
    {
        $framework = $this->mockContaoFrameworkWithInputAndConfig(null, false, true);

        $defaults = [
            'pageModel' => $this->createMock(PageModel::class),
            'parameters' => '/foo/bar/bar',
        ];

        $GLOBALS['TL_AUTO_ITEM'] = ['bar'];

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('"bar" is an auto_item keyword (duplicate content)');

        $enhancer = new InputEnhancer($framework);
        $enhancer->enhance($defaults, $this->createMock(Request::class));
    }

    private function mockContaoFrameworkWithInputAndConfig($input = null, bool $addLanguageToUrl = false, bool $useAutoItem = false)
    {
        $config = $this->mockAdapter(['get']);

        $config
            ->method('get')
            ->willReturnCallback(function ($param) use ($addLanguageToUrl, $useAutoItem) {
                if ('addLanguageToUrl' === $param) {
                    return $addLanguageToUrl;
                }

                if ('useAutoItem' === $param) {
                    return $useAutoItem;
                }

                return null;
            })
        ;

        if (null === $input) {
            $input = $this->mockAdapter(['get', 'setGet', 'post', 'setPost']);
        }

        $framework = $this->mockContaoFramework([Input::class => $input, Config::class => $config]);

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        return $framework;
    }
}
