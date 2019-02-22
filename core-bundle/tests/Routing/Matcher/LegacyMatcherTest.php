<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\Matcher;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Matcher\LegacyMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Input;
use Contao\PageModel;
use Contao\System;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * @group legacy
 */
class LegacyMatcherTest extends TestCase
{
    public function testDoesNothingIfNoHooksAreRegistered(): void
    {
        unset($GLOBALS['TL_HOOKS']['getPageIdFromUrl']);

        $matcher = new LegacyMatcher(
            $this->mockContaoFramework(),
            $this->mockRequestMatcher($this->once()),
            '.html',
            false
        );

        $request = $this->createMock(Request::class);
        $request
            ->method('getPathInfo')
            ->willReturn('foo.html')
        ;

        $matcher->matchRequest($request);
    }

    /**
     * @dataProvider getRootRequestData
     */
    public function testDoesNotExecuteHooksIfTheRequestPathIsEmpty(string $pathInfo, bool $prependLocale, bool $noRouteFound = false): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getPathInfo')
            ->willReturn($pathInfo)
        ;

        $GLOBALS['TL_HOOKS']['getPageIdFromUrl'] = ['foo', 'bar'];

        $matcher = new LegacyMatcher(
            $this->mockFrameworkWithAdapters(),
            $this->mockRequestMatcher($noRouteFound ? $this->never() : $this->once()),
            '.html',
            $prependLocale
        );

        if ($noRouteFound) {
            $this->expectException(ResourceNotFoundException::class);
        }

        $matcher->matchRequest($request);
    }

    public function getRootRequestData(): \Generator
    {
        yield ['/', false];
        yield ['/', true];
        yield ['/en/', true];
        yield ['/de/', true];
        yield ['/fr-FR/', true];
        yield ['/es/', false, true];
        yield ['/fr-FR/', false, true];
    }

    /**
     * @dataProvider getRequestData
     */
    public function testMatchesRequestWithoutFolderUrl(string $requestPath, ?string $language, string $urlSuffix, bool $useAutoItem, string $resultPath, ...$hooks): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getPathInfo')
            ->willReturn($requestPath)
        ;

        $GLOBALS['TL_HOOKS']['getPageIdFromUrl'] = $hooks;

        $config = [
            'folderUrl' => false,
            'useAutoItem' => $useAutoItem,
        ];

        $framework = $this->mockFrameworkWithAdapters($this->mockConfigAdapter($config), $language, $hooks);

        $matcher = new LegacyMatcher(
            $framework,
            $this->mockRequestMatcher($this->once(), $resultPath),
            $urlSuffix,
            null !== $language
        );

        $matcher->matchRequest($request);
    }

    /**
     * Test Data:
     * 1. Request path
     * 2. Language or NULL if not in request path
     * 3. URL suffix
     * 4. True when autoItem is enabled
     * 5. Expected path after hooks
     * 6. One or more hook arrays with class, method, input array and option output array.
     */
    public function getRequestData(): \Generator
    {
        yield [
            '/foo.html',
            null,
            '.html',
            false,
            '/foo.html',
            ['foo', 'bar', ['foo']],
        ];

        yield [
            '/de/foo.html',
            'de',
            '.html',
            false,
            '/de/foo.html',
            ['foo', 'bar', ['foo']],
        ];

        yield [
            '/foo/bar/baz.html',
            null,
            '.html',
            false,
            '/foo/bar/baz.html',
            ['foo', 'bar', ['foo', 'bar', 'baz']],
        ];

        yield [
            '/fr/foo/bar/baz.html',
            'fr',
            '.html',
            false,
            '/fr/foo/bar/baz.html',
            ['foo', 'bar', ['foo', 'bar', 'baz']],
        ];

        yield [
            '/foo/',
            null,
            '/',
            false,
            '/foo/',
            ['foo', 'bar', ['foo']],
        ];

        yield [
            '/en/foo/bar/',
            null,
            '/',
            false,
            '/en/foo/bar/',
            ['foo', 'bar', ['en', 'foo', 'bar']],
        ];

        yield [
            '/en/foo/bar',
            'en',
            '',
            false,
            '/en/foo/bar',
            ['foo', 'bar', ['foo', 'bar']],
        ];

        yield [
            '/en/foo/bar',
            'en',
            '',
            true,
            '/en/foo/bar',
            ['foo', 'bar', ['foo', 'auto_item', 'bar']],
        ];

        yield [
            '/foo.html',
            null,
            '.html',
            false,
            '/bar.html',
            ['foo', 'bar', ['foo'], ['bar']],
        ];

        yield [
            '/foo/bar/baz.html',
            null,
            '.html',
            false,
            '/bar.html',
            ['foo', 'bar', ['foo', 'bar', 'baz'], ['bar']],
        ];

        yield [
            '/foo.html',
            null,
            '.html',
            false,
            '/baz.html',
            ['foo', 'bar', ['foo'], ['bar']],
            ['bar', 'baz', ['bar'], ['baz']],
        ];
    }

    public function testMatchRequestFromPathIfFolderUrlIsNotFound(): void
    {
        $config = [
            'folderUrl' => true,
            'useAutoItem' => false,
        ];

        $framework = $this->mockFrameworkWithAdapters(
            $this->mockConfigAdapter($config),
            null,
            [['foo', 'bar', ['foo']]]
        );

        $folderUrlMatched = 0;

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getPathInfo')
            ->willReturn('foo.html')
        ;

        $matcher = $this->createMock(RequestMatcherInterface::class);
        $matcher
            ->expects($this->exactly(2))
            ->method('matchRequest')
            ->with($this->callback(
                function (Request $incoming) use ($request, &$folderUrlMatched) {
                    if ($folderUrlMatched > 0) {
                        return true;
                    }

                    $folderUrlMatched = 1;

                    return $incoming === $request;
                }
            ))
            ->willReturnCallback(
                function () use (&$folderUrlMatched) {
                    if ($folderUrlMatched < 2) {
                        $folderUrlMatched = 2;
                        throw new ResourceNotFoundException('');
                    }

                    return [];
                }
            )
        ;

        $GLOBALS['TL_HOOKS']['getPageIdFromUrl'] = [['foo', 'bar']];

        $matcher = new LegacyMatcher($framework, $matcher, '.html', false);
        $matcher->matchRequest($request);
    }

    public function testMatchRequestFromPathIfFolderUrlHasNoModel(): void
    {
        $config = [
            'folderUrl' => true,
            'useAutoItem' => false,
        ];

        $framework = $this->mockFrameworkWithAdapters(
            $this->mockConfigAdapter($config),
            null,
            [['foo', 'bar', ['foo']]]
        );

        $folderUrlMatched = 0;

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getPathInfo')
            ->willReturn('foo.html')
        ;

        $matcher = $this->createMock(RequestMatcherInterface::class);
        $matcher
            ->expects($this->exactly(2))
            ->method('matchRequest')
            ->with($this->callback(
                function (Request $incoming) use ($request, &$folderUrlMatched) {
                    if ($folderUrlMatched > 0) {
                        return true;
                    }

                    $folderUrlMatched = 1;

                    return $incoming === $request;
                }
            ))
            ->willReturnCallback(
                function () use (&$folderUrlMatched) {
                    if ($folderUrlMatched < 2) {
                        $folderUrlMatched = 2;

                        return [];
                    }

                    return [];
                }
            )
        ;

        $GLOBALS['TL_HOOKS']['getPageIdFromUrl'] = [['foo', 'bar']];

        $matcher = new LegacyMatcher($framework, $matcher, '.html', false);
        $matcher->matchRequest($request);
    }

    public function testUsesPageAliasFromFolderUrlRoute(): void
    {
        $config = [
            'folderUrl' => true,
            'useAutoItem' => false,
        ];

        $framework = $this->mockFrameworkWithAdapters(
            $this->mockConfigAdapter($config),
            null,
            [['foo', 'bar', ['bar']]]
        );

        $folderUrlMatched = 0;

        $request = $this->createMock(Request::class);
        $request
            ->method('getPathInfo')
            ->willReturn('foo.html')
        ;

        $matcher = $this->createMock(RequestMatcherInterface::class);
        $matcher
            ->expects($this->exactly(2))
            ->method('matchRequest')
            ->with($this->callback(
                function (Request $incoming) use ($request, &$folderUrlMatched) {
                    if ($folderUrlMatched > 0) {
                        return true;
                    }

                    $folderUrlMatched = 1;

                    return $incoming === $request;
                }
            ))
            ->willReturnCallback(
                function () use (&$folderUrlMatched) {
                    if ($folderUrlMatched < 2) {
                        $folderUrlMatched = 2;

                        return [
                            'pageModel' => $this->mockClassWithProperties(PageModel::class, ['alias' => 'bar']),
                        ];
                    }

                    return [];
                }
            )
        ;

        $GLOBALS['TL_HOOKS']['getPageIdFromUrl'] = [['foo', 'bar']];

        $matcher = new LegacyMatcher($framework, $matcher, '.html', false);
        $matcher->matchRequest($request);
    }

    public function testMatchesFragmentsWithParametersFolderUrlRoute(): void
    {
        $config = [
            'folderUrl' => true,
            'useAutoItem' => false,
        ];

        $framework = $this->mockFrameworkWithAdapters(
            $this->mockConfigAdapter($config),
            null,
            [['foo', 'bar', ['foo', 'bar', 'baz']]]
        );

        $folderUrlMatched = 0;

        $request = $this->createMock(Request::class);
        $request
            ->method('getPathInfo')
            ->willReturn('foo/bar/baz.html')
        ;

        $matcher = $this->createMock(RequestMatcherInterface::class);
        $matcher
            ->expects($this->exactly(2))
            ->method('matchRequest')
            ->with($this->callback(
                function (Request $incoming) use ($request, &$folderUrlMatched) {
                    if ($folderUrlMatched > 0) {
                        return true;
                    }

                    $folderUrlMatched = 1;

                    return $incoming === $request;
                }
            ))
            ->willReturnCallback(
                function () use (&$folderUrlMatched) {
                    if ($folderUrlMatched < 2) {
                        $folderUrlMatched = 2;

                        return [
                            'pageModel' => $this->mockClassWithProperties(PageModel::class, ['alias' => 'foo']),
                            'parameters' => '/bar/baz',
                        ];
                    }

                    return [];
                }
            )
        ;

        $GLOBALS['TL_HOOKS']['getPageIdFromUrl'] = [['foo', 'bar']];

        $matcher = new LegacyMatcher($framework, $matcher, '.html', false);
        $matcher->matchRequest($request);
    }

    public function testAddsAutoItemToFragmentsOfFolderUrlRoute(): void
    {
        $config = [
            'folderUrl' => true,
            'useAutoItem' => true,
        ];

        $framework = $this->mockFrameworkWithAdapters(
            $this->mockConfigAdapter($config),
            null,
            [['foo', 'bar', ['foo', 'auto_item', 'baz']]]
        );

        $folderUrlMatched = 0;

        $request = $this->createMock(Request::class);
        $request
            ->method('getPathInfo')
            ->willReturn('foo/baz.html')
        ;

        $matcher = $this->createMock(RequestMatcherInterface::class);
        $matcher
            ->expects($this->exactly(2))
            ->method('matchRequest')
            ->with($this->callback(
                function (Request $incoming) use ($request, &$folderUrlMatched) {
                    if ($folderUrlMatched > 0) {
                        return true;
                    }

                    $folderUrlMatched = 1;

                    return $incoming === $request;
                }
            ))
            ->willReturnCallback(
                function () use (&$folderUrlMatched) {
                    if ($folderUrlMatched < 2) {
                        $folderUrlMatched = 2;

                        return [
                            'pageModel' => $this->mockClassWithProperties(PageModel::class, ['alias' => 'foo']),
                            'parameters' => '/baz',
                        ];
                    }

                    return [];
                }
            )
        ;

        $GLOBALS['TL_HOOKS']['getPageIdFromUrl'] = [['foo', 'bar']];

        $matcher = new LegacyMatcher($framework, $matcher, '.html', false);
        $matcher->matchRequest($request);
    }

    public function testThrowsExceptionIfUrlSuffixDoesNotMatch(): void
    {
        $config = [
            'folderUrl' => false,
            'useAutoItem' => false,
        ];

        $framework = $this->mockFrameworkWithAdapters($this->mockConfigAdapter($config));

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getPathInfo')
            ->willReturn('foo.php')
        ;

        $GLOBALS['TL_HOOKS']['getPageIdFromUrl'] = [[]];

        $matcher = new LegacyMatcher($framework, $this->mockRequestMatcher($this->never()), '.html', false);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('URL suffix does not match');

        $matcher->matchRequest($request);
    }

    public function testThrowsExceptionIfLanguageIsMissing(): void
    {
        $config = [
            'folderUrl' => false,
            'useAutoItem' => false,
        ];

        $framework = $this->mockFrameworkWithAdapters($this->mockConfigAdapter($config));

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getPathInfo')
            ->willReturn('foo/bar.html')
        ;

        $GLOBALS['TL_HOOKS']['getPageIdFromUrl'] = [[]];

        $matcher = new LegacyMatcher($framework, $this->mockRequestMatcher($this->never()), '.html', true);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Locale does not match');

        $matcher->matchRequest($request);
    }

    public function testThrowsExceptionIfHookReturnsAnEmptyAlias(): void
    {
        $config = [
            'folderUrl' => false,
            'useAutoItem' => false,
        ];

        $framework = $this->mockFrameworkWithAdapters(
            $this->mockConfigAdapter($config),
            null,
            [['foo', 'bar', ['foo'], ['']]]
        );

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getPathInfo')
            ->willReturn('foo.html')
        ;

        $GLOBALS['TL_HOOKS']['getPageIdFromUrl'] = [['foo', 'bar']];

        $matcher = new LegacyMatcher($framework, $this->mockRequestMatcher($this->never()), '.html', false);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Page alias is empty');

        $matcher->matchRequest($request);
    }

    /**
     * @return ContaoFramework|MockObject
     */
    private function mockFrameworkWithAdapters(Adapter $configAdapter = null, string $language = null, array $hooks = []): ContaoFramework
    {
        $classes = [];
        $callbacks = [];

        foreach ($hooks as $hook) {
            $classes[] = [$hook[0]];

            $callback = $this->mockAdapter([$hook[1]]);
            $callback
                ->expects($this->once())
                ->method($hook[1])
                ->with($hook[2])
                ->willReturn($hook[3] ?? $hook[2])
            ;

            $callbacks[] = $callback;
        }

        $systemAdapter = $this->mockAdapter(['importStatic']);
        $systemAdapter
            ->expects($this->exactly(\count($hooks)))
            ->method('importStatic')
            ->withConsecutive(...$classes)
            ->willReturnOnConsecutiveCalls(...$callbacks)
        ;

        $inputAdapter = $this->mockAdapter(['setGet']);
        $inputAdapter
            ->expects(null === $language ? $this->never() : $this->once())
            ->method('setGet')
            ->with('language', $language)
        ;

        $framework = $this->mockContaoFramework([
            System::class => $systemAdapter,
            Input::class => $inputAdapter,
            Config::class => $configAdapter,
        ]);

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        return $framework;
    }

    /**
     * @return RequestMatcherInterface|MockObject
     */
    private function mockRequestMatcher(Invocation $expects, string $pathInfo = null, array $match = []): RequestMatcherInterface
    {
        $matcher = $this->createMock(RequestMatcherInterface::class);
        $matcher
            ->expects($expects)
            ->method('matchRequest')
            ->with($this->callback(
                function (Request $request) use ($pathInfo) {
                    return null === $pathInfo || $request->getPathInfo() === $pathInfo;
                }
            ))
            ->willReturn($match)
        ;

        return $matcher;
    }

    /**
     * @return Adapter|MockObject
     */
    private function mockConfigAdapter(array $config): Adapter
    {
        $configAdapter = $this->mockAdapter(['get']);
        $configAdapter
            ->method('get')
            ->willReturnCallback(
                function ($param) use ($config) {
                    return $config[$param] ?? null;
                }
            )
        ;

        return $configAdapter;
    }
}
