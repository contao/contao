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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class InputEnhancerTest extends TestCase
{
    public function testReturnsTheDefaultsIfThereIsNoPageModel(): void
    {
        $framework = $this->createContaoFrameworkMock();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $enhancer = new InputEnhancer($framework, new RequestStack());
        $enhancer->enhance([], Request::create('/'));
    }

    #[DataProvider('getLocales')]
    public function testSetsTheLanguageWithUrlPrefix(string $urlPrefix, string $language): void
    {
        $input = $this->createAdapterMock(['setGet']);
        $input
            ->expects('' !== $urlPrefix ? $this->once() : $this->never())
            ->method('setGet')
            ->with('language', $language)
        ;

        $framework = $this->createContaoFrameworkStub([Input::class => $input]);

        $request = Request::create('/');
        $requestStack = new RequestStack([$request]);

        $defaults = [
            'pageModel' => $this->mockPageModel($language, $urlPrefix),
        ];

        $enhancer = new InputEnhancer($framework, $requestStack);
        $enhancer->enhance($defaults, $request);
    }

    public static function getLocales(): iterable
    {
        yield ['', 'en'];
        yield ['', 'de'];
        yield ['de', 'de'];
        yield ['en', 'en'];
        yield ['foo', 'de'];
        yield ['bar', 'en'];
    }

    #[DataProvider('getParameters')]
    public function testAddsParameters(string $parameters, array ...$setters): void
    {
        $matcher = $this->exactly(\count($setters));

        $input = $this->createAdapterMock(['setGet', 'setUnusedRouteParameters']);
        $input
            ->expects($matcher)
            ->method('setGet')
            ->with($this->callback(
                static fn (...$parameters) => $setters[$matcher->numberOfInvocations() - 1] === $parameters,
            ))
        ;

        $input
            ->expects($this->once())
            ->method('setUnusedRouteParameters')
            ->with(array_map(static fn ($setter) => $setter[0], $setters))
        ;

        $framework = $this->createContaoFrameworkStub([Input::class => $input]);

        $request = Request::create('/');
        $requestStack = new RequestStack([$request]);

        $defaults = [
            'pageModel' => $this->mockPageModel('en', ''),
            'parameters' => $parameters,
        ];

        $enhancer = new InputEnhancer($framework, $requestStack);
        $enhancer->enhance($defaults, $request);
    }

    public static function getParameters(): iterable
    {
        yield ['/foo/bar', ['foo', 'bar']];
        yield ['/foo/bar/bar/baz', ['foo', 'bar'], ['bar', 'baz']];
        yield ['/foo/bar/baz', ['auto_item', 'foo'], ['bar', 'baz']];
        yield ['/f%20o/bar', ['f%20o', 'bar']];
        yield ['/foo/ba%20r', ['foo', 'ba%20r']];
    }

    public function testThrowsAnExceptionUponDuplicateParameters(): void
    {
        $input = $this->createAdapterMock(['setGet']);
        $input
            ->expects($this->once())
            ->method('setGet')
        ;

        $framework = $this->createContaoFrameworkStub([Input::class => $input]);

        $request = Request::create('/');
        $requestStack = new RequestStack([$request]);

        $defaults = [
            'pageModel' => $this->createStub(PageModel::class),
            'parameters' => '/foo/bar/foo/bar',
        ];

        $enhancer = new InputEnhancer($framework, $requestStack);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Duplicate parameter "foo" in path');

        $enhancer->enhance($defaults, $request);
    }

    public function testThrowsAnExceptionUponParametersInQuery(): void
    {
        $input = $this->createAdapterMock(['setGet']);
        $input
            ->expects($this->never())
            ->method('setGet')
        ;

        $framework = $this->createContaoFrameworkStub([Input::class => $input]);

        $request = Request::create('/?foo=bar');
        $requestStack = new RequestStack([$request]);

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
        $input = $this->createAdapterMock(['setGet']);
        $input
            ->expects($this->once())
            ->method('setGet')
            ->with('foo', 'bar')
        ;

        $adapters = [
            Input::class => $input,
            Config::class => $this->createConfiguredAdapterStub(['get' => false]),
        ];

        $framework = $this->createContaoFrameworkStub($adapters);

        $request = Request::create('/');
        $requestStack = new RequestStack([$request]);

        $defaults = [
            'pageModel' => $this->mockPageModel('en', ''),
            'parameters' => '/foo/bar//baz',
        ];

        $enhancer = new InputEnhancer($framework, $requestStack);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Empty fragment key in path');

        $enhancer->enhance($defaults, $request);
    }

    private function mockPageModel(string $language, string $urlPrefix): PageModel&Stub
    {
        return $this->createClassWithPropertiesStub(PageModel::class, [
            'rootLanguage' => $language,
            'urlPrefix' => $urlPrefix,
        ]);
    }
}
