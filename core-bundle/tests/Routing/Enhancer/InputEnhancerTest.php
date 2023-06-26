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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class InputEnhancerTest extends TestCase
{
    public function testReturnsTheDefaultsIfThereIsNoPageModel(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $enhancer = new InputEnhancer($framework, new RequestStack());
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

        $request = Request::create('/');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $defaults = [
            'pageModel' => $this->mockPageModel($language, $urlPrefix),
        ];

        $enhancer = new InputEnhancer($framework, $requestStack);
        $enhancer->enhance($defaults, $request);
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
    public function testAddsParameters(string $parameters, array ...$setters): void
    {
        $input = $this->mockAdapter(['setGet', 'setUnusedRouteParameters']);
        $input
            ->expects($this->exactly(\count($setters)))
            ->method('setGet')
            ->withConsecutive(...$setters)
        ;

        $input
            ->expects($this->once())
            ->method('setUnusedRouteParameters')
            ->with(array_map(static fn ($setter) => $setter[0], $setters))
        ;

        $framework = $this->mockContaoFramework([Input::class => $input]);

        $request = Request::create('/');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $defaults = [
            'pageModel' => $this->mockPageModel('en', ''),
            'parameters' => $parameters,
        ];

        $enhancer = new InputEnhancer($framework, $requestStack);
        $enhancer->enhance($defaults, $request);
    }

    public function getParameters(): \Generator
    {
        yield ['/foo/bar', ['foo', 'bar']];
        yield ['/foo/bar/bar/baz', ['foo', 'bar'], ['bar', 'baz']];
        yield ['/foo/bar/baz', ['auto_item', 'foo'], ['bar', 'baz']];
        yield ['/f%20o/bar', ['f%20o', 'bar']];
        yield ['/foo/ba%20r', ['foo', 'ba%20r']];
    }

    public function testThrowsAnExceptionUponDuplicateParameters(): void
    {
        $input = $this->mockAdapter(['setGet']);
        $input
            ->expects($this->once())
            ->method('setGet')
        ;

        $framework = $this->mockContaoFramework([Input::class => $input]);

        $request = Request::create('/');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $defaults = [
            'pageModel' => $this->createMock(PageModel::class),
            'parameters' => '/foo/bar/foo/bar',
        ];

        $enhancer = new InputEnhancer($framework, $requestStack);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Duplicate parameter "foo" in path');

        $enhancer->enhance($defaults, $request);
    }

    public function testThrowsAnExceptionUponParametersInQuery(): void
    {
        $input = $this->mockAdapter(['setGet']);
        $input
            ->expects($this->never())
            ->method('setGet')
        ;

        $framework = $this->mockContaoFramework([Input::class => $input]);

        $request = Request::create('/?foo=bar');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $defaults = [
            'pageModel' => $this->mockPageModel('en', ''),
            'parameters' => '/foo/bar',
        ];

        $enhancer = new InputEnhancer($framework, $requestStack);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Duplicate parameter "foo" in path');

        $enhancer->enhance($defaults, $request);
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

        $request = Request::create('/');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $defaults = [
            'pageModel' => $this->mockPageModel('en', ''),
            'parameters' => '/foo/bar//baz',
        ];

        $enhancer = new InputEnhancer($framework, $requestStack);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Empty fragment key in path');

        $enhancer->enhance($defaults, $request);
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
