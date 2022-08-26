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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class InputEnhancerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_AUTO_ITEM']);

        parent::tearDown();
    }

    public function testReturnsTheDefaultsIfThereIsNoPageModel(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $enhancer = new InputEnhancer($framework);
        $enhancer->enhance([], Request::create('/'));
    }

    /**
     * @dataProvider getLocales
     */
    public function testSetsTheLanguageWithUrlPrefix(string $urlPrefix, string $language): void
    {
        $input = $this->mockAdapter(['setGet']);
        $input
            ->expects('' !== $urlPrefix ? $this->once() : $this->never())
            ->method('setGet')
            ->with('language', $language)
        ;

        $framework = $this->mockContaoFramework([Input::class => $input]);

        $defaults = [
            'pageModel' => $this->mockPageModel($language, $urlPrefix),
        ];

        $enhancer = new InputEnhancer($framework);
        $enhancer->enhance($defaults, Request::create('/'));
    }

    public function getLocales(): \Generator
    {
        yield ['', 'en'];
        yield ['', 'de'];
        yield ['de', 'de'];
        yield ['en', 'en'];
        yield ['foo', 'de'];
        yield ['bar', 'en'];
    }

    /**
     * @dataProvider getParameters
     */
    public function testAddsParameters(string $parameters, bool $useAutoItem, array ...$setters): void
    {
        // Input::setGet must always be called with $blnAddUnused=true
        array_walk(
            $setters,
            static function (array &$set): void {
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
            'pageModel' => $this->mockPageModel('en', ''),
            'parameters' => $parameters,
        ];

        $enhancer = new InputEnhancer($framework);
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
            ->expects($this->once())
            ->method('setGet')
        ;

        $framework = $this->mockContaoFramework([Input::class => $input]);

        $defaults = [
            'pageModel' => $this->createMock(PageModel::class),
            'parameters' => '/foo/bar/foo/bar',
        ];

        $enhancer = new InputEnhancer($framework);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Duplicate parameter "foo" in path');

        $enhancer->enhance($defaults, Request::create('/'));
    }

    public function testThrowsAnExceptionUponParametersInQuery(): void
    {
        $input = $this->mockAdapter(['setGet']);
        $input
            ->expects($this->never())
            ->method('setGet')
        ;

        $framework = $this->mockContaoFramework([Input::class => $input]);

        $defaults = [
            'pageModel' => $this->mockPageModel('en', ''),
            'parameters' => '/foo/bar',
        ];

        $enhancer = new InputEnhancer($framework);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Duplicate parameter "foo" in path');

        $enhancer->enhance($defaults, Request::create('/?foo=bar'));
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
            'pageModel' => $this->mockPageModel('en', ''),
            'parameters' => '/foo/bar/bar',
        ];

        $GLOBALS['TL_AUTO_ITEM'] = ['bar'];

        $enhancer = new InputEnhancer($framework);

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
            'pageModel' => $this->mockPageModel('en', ''),
            'parameters' => '/foo/bar/baz',
        ];

        $enhancer = new InputEnhancer($framework);

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
            'pageModel' => $this->mockPageModel('en', ''),
            'parameters' => '/foo/bar//baz',
        ];

        $enhancer = new InputEnhancer($framework);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Empty fragment key in path');

        $enhancer->enhance($defaults, Request::create('/'));
    }

    /**
     * @return PageModel&MockObject
     */
    private function mockPageModel(string $language, string $urlPrefix): PageModel
    {
        return $this->mockClassWithProperties(
            PageModel::class,
            [
                'rootLanguage' => $language,
                'urlPrefix' => $urlPrefix,
            ]
        );
    }
}
