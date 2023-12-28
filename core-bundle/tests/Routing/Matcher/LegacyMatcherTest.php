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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * @group legacy
 */
class LegacyMatcherTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_HOOKS']);

        parent::tearDown();
    }

    public function testDoesNothingIfNoHooksAreRegistered(): void
    {
        unset($GLOBALS['TL_HOOKS']['getPageIdFromUrl']);

        $matcher = new LegacyMatcher(
            $this->mockContaoFramework(),
            $this->mockRequestMatcher(),
            '.html',
            false
        );

        $matcher->matchRequest(Request::create('foo.html'));
    }

    /**
     * @dataProvider getRootRequestData
     */
    public function testDoesNotExecuteHooksIfTheRequestPathIsEmpty(string $pathInfo, bool $prependLocale, bool $noRouteFound = false): void
    {
        $GLOBALS['TL_HOOKS']['getPageIdFromUrl'] = ['foo', 'bar'];

        $matcher = new LegacyMatcher(
            $this->mockFrameworkWithAdapters(),
            $this->mockRequestMatcher(),
            '.html',
            $prependLocale
        );

        if ($noRouteFound) {
            $this->expectException(ResourceNotFoundException::class);
        }

        $matcher->matchRequest(Request::create($pathInfo));
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
     * @param array $hooks
     *
     * @group legacy
     * @dataProvider getRequestData
     */
    public function testMatchesRequestWithoutFolderUrl(string $requestPath, ?string $language, string $urlSuffix, bool $useAutoItem, string $resultPath, ...$hooks): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.0: Using the "getPageIdFromUrl" hook has been deprecated %s');

        $GLOBALS['TL_HOOKS']['getPageIdFromUrl'] = $hooks;

        $config = [
            'useAutoItem' => $useAutoItem,
        ];

        $framework = $this->mockFrameworkWithAdapters($this->mockConfigAdapter($config), $language, $hooks);

        $matcher = new LegacyMatcher(
            $framework,
            $this->mockRequestMatcher($resultPath),
            $urlSuffix,
            null !== $language
        );

        $matcher->matchRequest(Request::create($requestPath));
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

    /**
     * @group legacy
     */
    public function testMatchRequestFromPathIfFolderUrlIsNotFound(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.0: Using the "getPageIdFromUrl" hook has been deprecated %s');

        $config = [
            'useAutoItem' => false,
        ];

        $framework = $this->mockFrameworkWithAdapters(
            $this->mockConfigAdapter($config),
            null,
            [['foo', 'bar', ['foo']]]
        );

        $folderUrlMatched = 0;
        $request = Request::create('foo.html');

        $matcher = $this->createMock(RequestMatcherInterface::class);
        $matcher
            ->expects($this->exactly(2))
            ->method('matchRequest')
            ->with($this->callback(
                static function (Request $incoming) use ($request, &$folderUrlMatched) {
                    if ($folderUrlMatched > 0) {
                        return true;
                    }

                    $folderUrlMatched = 1;

                    return $incoming === $request;
                }
            ))
            ->willReturnCallback(
                static function () use (&$folderUrlMatched) {
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

    /**
     * @group legacy
     */
    public function testMatchRequestFromPathIfFolderUrlHasNoModel(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.0: Using the "getPageIdFromUrl" hook has been deprecated %s');

        $config = [
            'useAutoItem' => false,
        ];

        $framework = $this->mockFrameworkWithAdapters(
            $this->mockConfigAdapter($config),
            null,
            [['foo', 'bar', ['foo']]]
        );

        $folderUrlMatched = 0;
        $request = Request::create('foo.html');

        $matcher = $this->createMock(RequestMatcherInterface::class);
        $matcher
            ->expects($this->exactly(2))
            ->method('matchRequest')
            ->with($this->callback(
                static function (Request $incoming) use ($request, &$folderUrlMatched) {
                    if ($folderUrlMatched > 0) {
                        return true;
                    }

                    $folderUrlMatched = 1;

                    return $incoming === $request;
                }
            ))
            ->willReturnCallback(
                static function () use (&$folderUrlMatched) {
                    if ($folderUrlMatched < 2) {
                        $folderUrlMatched = 2;
                    }

                    return [];
                }
            )
        ;

        $GLOBALS['TL_HOOKS']['getPageIdFromUrl'] = [['foo', 'bar']];

        $matcher = new LegacyMatcher($framework, $matcher, '.html', false);
        $matcher->matchRequest($request);
    }

    /**
     * @group legacy
     */
    public function testUsesPageAliasFromFolderUrlRoute(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.0: Using the "getPageIdFromUrl" hook has been deprecated %s');

        $config = [
            'useAutoItem' => false,
        ];

        $framework = $this->mockFrameworkWithAdapters(
            $this->mockConfigAdapter($config),
            null,
            [['foo', 'bar', ['bar']]]
        );

        $folderUrlMatched = 0;
        $request = Request::create('foo.html');

        $matcher = $this->createMock(RequestMatcherInterface::class);
        $matcher
            ->expects($this->exactly(2))
            ->method('matchRequest')
            ->with($this->callback(
                static function (Request $incoming) use ($request, &$folderUrlMatched) {
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

                        $pageModel = $this->mockClassWithProperties(PageModel::class);
                        $pageModel->alias = 'bar';

                        return ['pageModel' => $pageModel];
                    }

                    return [];
                }
            )
        ;

        $GLOBALS['TL_HOOKS']['getPageIdFromUrl'] = [['foo', 'bar']];

        $matcher = new LegacyMatcher($framework, $matcher, '.html', false);
        $matcher->matchRequest($request);
    }

    /**
     * @group legacy
     */
    public function testMatchesFragmentsWithParametersFolderUrlRoute(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.0: Using the "getPageIdFromUrl" hook has been deprecated %s');

        $config = [
            'useAutoItem' => false,
        ];

        $framework = $this->mockFrameworkWithAdapters(
            $this->mockConfigAdapter($config),
            null,
            [['foo', 'bar', ['foo', 'bar', 'baz']]]
        );

        $folderUrlMatched = 0;
        $request = Request::create('foo/bar/baz.html');

        $matcher = $this->createMock(RequestMatcherInterface::class);
        $matcher
            ->expects($this->exactly(2))
            ->method('matchRequest')
            ->with($this->callback(
                static function (Request $incoming) use ($request, &$folderUrlMatched) {
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

                        $pageModel = $this->mockClassWithProperties(PageModel::class);
                        $pageModel->alias = 'foo';

                        return [
                            'pageModel' => $pageModel,
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

    /**
     * @group legacy
     */
    public function testAddsAutoItemToFragmentsOfFolderUrlRoute(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.0: Using the "getPageIdFromUrl" hook has been deprecated %s');

        $config = [
            'useAutoItem' => true,
        ];

        $framework = $this->mockFrameworkWithAdapters(
            $this->mockConfigAdapter($config),
            null,
            [['foo', 'bar', ['foo', 'auto_item', 'baz']]]
        );

        $folderUrlMatched = 0;
        $request = Request::create('foo/baz.html');

        $matcher = $this->createMock(RequestMatcherInterface::class);
        $matcher
            ->expects($this->exactly(2))
            ->method('matchRequest')
            ->with($this->callback(
                static function (Request $incoming) use ($request, &$folderUrlMatched) {
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

                        $pageModel = $this->mockClassWithProperties(PageModel::class);
                        $pageModel->alias = 'foo';

                        return [
                            'pageModel' => $pageModel,
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
            'useAutoItem' => false,
        ];

        $GLOBALS['TL_HOOKS']['getPageIdFromUrl'] = [[]];

        $framework = $this->mockFrameworkWithAdapters($this->mockConfigAdapter($config));
        $matcher = new LegacyMatcher($framework, $this->mockRequestMatcher(), '.html', false);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('URL suffix does not match');

        $matcher->matchRequest(Request::create('foo.php'));
    }

    public function testThrowsExceptionIfLanguageIsMissing(): void
    {
        $config = [
            'folderUrl' => false,
            'useAutoItem' => false,
        ];

        $GLOBALS['TL_HOOKS']['getPageIdFromUrl'] = [[]];

        $framework = $this->mockFrameworkWithAdapters($this->mockConfigAdapter($config));
        $matcher = new LegacyMatcher($framework, $this->mockRequestMatcher(), '.html', true);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Locale does not match');

        $matcher->matchRequest(Request::create('foo/bar.html'));
    }

    /**
     * @group legacy
     */
    public function testThrowsExceptionIfHookReturnsAnEmptyAlias(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.0: Using the "getPageIdFromUrl" hook has been deprecated %s');

        $config = [
            'useAutoItem' => false,
        ];

        $framework = $this->mockFrameworkWithAdapters(
            $this->mockConfigAdapter($config),
            null,
            [['foo', 'bar', ['foo'], ['']]]
        );

        $GLOBALS['TL_HOOKS']['getPageIdFromUrl'] = [['foo', 'bar']];

        $matcher = new LegacyMatcher($framework, $this->mockRequestMatcher(), '.html', false);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Page alias is empty');

        $matcher->matchRequest(Request::create('foo.html'));
    }

    /**
     * @phpstan-param Adapter<Config> $configAdapter
     *
     * @return ContaoFramework&MockObject
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
     * @return RequestMatcherInterface&MockObject
     */
    private function mockRequestMatcher(string $pathInfo = null, array $match = []): RequestMatcherInterface
    {
        $expectCalls = null === $pathInfo ? 1 : 2;

        $matcher = $this->createMock(RequestMatcherInterface::class);
        $matcher
            ->expects($this->exactly($expectCalls))
            ->method('matchRequest')
            ->with($this->callback(
                static function (Request $request) use ($pathInfo, &$expectCalls) {
                    if (1 === $expectCalls) {
                        return null === $pathInfo || $request->getPathInfo() === $pathInfo;
                    }

                    --$expectCalls;

                    return true;
                }
            ))
            ->willReturn($match)
        ;

        return $matcher;
    }

    /**
     * @return Adapter<Config>&MockObject
     */
    private function mockConfigAdapter(array $config): Adapter
    {
        $configAdapter = $this->mockAdapter(['get']);
        $configAdapter
            ->method('get')
            ->willReturnCallback(static fn ($param) => $config[$param] ?? null)
        ;

        return $configAdapter;
    }
}
