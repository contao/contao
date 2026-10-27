<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing;

use Contao\CoreBundle\Routing\AbstractPageRouteProvider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Route;

class AbstractPageRouteProviderTest extends TestCase
{
    /**
     * @dataProvider transitiveRoutesProvider
     */
    public function testRouteComparisonIsTransitive(array $routes, array|null $languages): void
    {
        $instance = $this->getMockForAbstractClass(AbstractPageRouteProvider::class, [], '', false);
        $class = new \ReflectionClass($instance);

        if (null !== $languages) {
            $languages = $class->getMethod('convertLanguagesForSorting')->invoke($instance, $languages);
        }

        $method = $class->getMethod('compareRoutes');
        $comparisons = [];

        foreach ($routes as $nameA => $a) {
            foreach ($routes as $nameB => $b) {
                $comparisons[$nameA][$nameB] = $method->invoke($instance, $a, $b, $languages) <=> 0;
            }
        }

        // Include ties: A <= B and B <= C must imply A <= C.
        foreach (array_keys($routes) as $a) {
            foreach (array_keys($routes) as $b) {
                foreach (array_keys($routes) as $c) {
                    if ($comparisons[$a][$b] <= 0 && $comparisons[$b][$c] <= 0) {
                        $this->assertLessThanOrEqual(
                            0,
                            $comparisons[$a][$c],
                            \sprintf('%s <= %s and %s <= %s, but %s > %s', $a, $b, $b, $c, $a, $c),
                        );
                    }
                }
            }
        }
    }

    public function transitiveRoutesProvider(): iterable
    {
        $rootB = $this->mockPageModel('nl', true, true, 256);
        $rootB->domain = 'b.example.com';

        $indexC = $this->mockPageModel('nl', true, false, 384);
        $indexC->domain = 'c.example.com';

        $indexA = $this->mockPageModel('zh', true, false, 128);
        $indexA->domain = 'a.example.com';

        yield 'Host grouping prevents conflicting language and page type ordering across domains' => [
            [
                'Domain B root' => new Route('/', ['pageModel' => $rootB], [], [], $rootB->domain),
                'Domain C index' => new Route('/', ['pageModel' => $indexC], [], [], $indexC->domain),
                'Domain A index' => new Route('/', ['pageModel' => $indexA], [], [], $indexA->domain),
            ],
            ['en', 'de'],
        ];

        yield 'Shared language fallback changes the priority depending on the other route' => [
            [
                'German (Germany)' => new Route('/', ['pageModel' => $this->mockPageModel('de-DE')]),
                'German' => new Route('/', ['pageModel' => $this->mockPageModel('de')]),
                'English' => new Route('/', ['pageModel' => $this->mockPageModel('en')]),
            ],
            ['de-CH', 'en', 'de-DE'],
        ];

        yield 'Regional root ordering conflicts with fallback and page type ordering' => [
            [
                'Swiss German root' => new Route('/', ['pageModel' => $this->mockPageModel('de-CH', false, true, 128)]),
                'German fallback index' => new Route('/', ['pageModel' => $this->mockPageModel('de-DE', true, false, 256)]),
                'French index' => new Route('/', ['pageModel' => $this->mockPageModel('fr', false, false, 384)]),
            ],
            ['en'],
        ];

        yield 'Identical locales skip root ordering used for different regions' => [
            [
                'First Swiss German index' => new Route('/', ['pageModel' => $this->mockPageModel('de-CH', false, false, 128)]),
                'German index' => new Route('/', ['pageModel' => $this->mockPageModel('de-DE', false, false, 256)]),
                'Last Swiss German index with higher priority' => new Route('/', ['pageModel' => $this->mockPageModel('de-CH', false, false, 384, 1)]),
            ],
            ['en'],
        ];

        yield 'Custom parameter requirement compares equal to both required and optional parameters' => [
            [
                'Required' => new Route('/foo{!parameters}', ['pageModel' => $this->mockPageModel('en')], ['parameters' => '/.+?']),
                'Optional' => new Route('/foo{!parameters}', ['pageModel' => $this->mockPageModel('en')], ['parameters' => '(/.+?)?']),
                'Custom' => new Route('/foo{!parameters}', ['pageModel' => $this->mockPageModel('en')], ['parameters' => '/[0-9]+']),
            ],
            null,
        ];

        yield 'Parameter ordering uses case-sensitive paths but path ordering ignores case' => [
            [
                'Required lowercase' => new Route('/foo{!parameters}', ['pageModel' => $this->mockPageModel('en')], ['parameters' => '/.+?']),
                'Optional lowercase' => new Route('/foo{!parameters}', ['pageModel' => $this->mockPageModel('en')], ['parameters' => '(/.+?)?']),
                'Required uppercase' => new Route('/FOO{!parameters}', ['pageModel' => $this->mockPageModel('en')], ['parameters' => '/.+?']),
            ],
            null,
        ];
    }

    /**
     * @dataProvider compareRoutesProvider
     */
    public function testCompareRoutes(Route $a, Route $b, array|null $languages, int $expected): void
    {
        $instance = $this->getMockForAbstractClass(AbstractPageRouteProvider::class, [], '', false);
        $class = new \ReflectionClass($instance);

        if (null !== $languages) {
            $method = $class->getMethod('convertLanguagesForSorting');
            $languages = $method->invoke($instance, $languages);
        }

        $method = $class->getMethod('compareRoutes');
        $result = $method->invoke($instance, $a, $b, $languages);

        $this->assertSame($expected, $result);
    }

    public function compareRoutesProvider(): iterable
    {
        yield 'Sorts route with host higher' => [
            new Route('', [], [], [], 'www.example.com'),
            new Route('', [], [], [], ''),
            null,
            -1,
        ];

        yield 'Sorts route without host lower' => [
            new Route('', [], [], [], ''),
            new Route('', [], [], [], 'www.example.com'),
            null,
            1,
        ];

        yield 'Sorting unknown if route has no PageModel' => [
            new Route('', [], [], [], 'www.example.com'),
            new Route('', [], [], [], 'www.example.com'),
            null,
            0,
        ];

        yield 'Sorts route higher if it is fallback and no languages match' => [
            new Route('', ['pageModel' => $this->mockPageModel('en', true)]),
            new Route('', ['pageModel' => $this->mockPageModel('de')]),
            null,
            -1,
        ];

        yield 'Sorts route lower if it is not fallback and no languages match' => [
            new Route('', ['pageModel' => $this->mockPageModel('en')]),
            new Route('', ['pageModel' => $this->mockPageModel('de', true)]),
            null,
            1,
        ];

        yield 'Sorts route higher if it matches a preferred language' => [
            new Route('', ['pageModel' => $this->mockPageModel('en')]),
            new Route('', ['pageModel' => $this->mockPageModel('de')]),
            ['en'],
            -1,
        ];

        yield 'Sorts route lower if it does not match a preferred language' => [
            new Route('', ['pageModel' => $this->mockPageModel('en')]),
            new Route('', ['pageModel' => $this->mockPageModel('de')]),
            ['de'],
            1,
        ];

        yield 'Sorts route higher if preferred language has higher priority' => [
            new Route('', ['pageModel' => $this->mockPageModel('en')]),
            new Route('', ['pageModel' => $this->mockPageModel('de')]),
            ['en', 'de'],
            -1,
        ];

        yield 'Sorts route lower lower if preferred language has lower priority' => [
            new Route('', ['pageModel' => $this->mockPageModel('en')]),
            new Route('', ['pageModel' => $this->mockPageModel('de')]),
            ['de', 'en'],
            1,
        ];

        yield 'Sorts route higher if preferred language has higher priority with region' => [
            new Route('', ['pageModel' => $this->mockPageModel('en-US')]),
            new Route('', ['pageModel' => $this->mockPageModel('de-CH')]),
            ['en-US', 'de-CH'],
            -1,
        ];

        yield 'Sorts route lower lower if preferred language has lower priority with region' => [
            new Route('', ['pageModel' => $this->mockPageModel('en-US')]),
            new Route('', ['pageModel' => $this->mockPageModel('de-CH')]),
            ['de-CH', 'en-US'],
            1,
        ];

        yield 'Sorts route by preferred language if region does not match' => [
            new Route('', ['pageModel' => $this->mockPageModel('en')]),
            new Route('', ['pageModel' => $this->mockPageModel('de')]),
            ['de-CH', 'en-US'],
            1,
        ];

        yield 'Sorts route by preferred language if one region matches' => [
            new Route('', ['pageModel' => $this->mockPageModel('en')]),
            new Route('', ['pageModel' => $this->mockPageModel('de')]),
            ['de-CH', 'en-US', 'en'],
            1,
        ];

        yield 'Sorts route by language (1)' => [
            new Route('', ['pageModel' => $this->mockPageModel('de-CH')]),
            new Route('', ['pageModel' => $this->mockPageModel('en-US')]),
            ['en', 'de'],
            1,
        ];

        yield 'Sorts route by language (2)' => [
            new Route('', ['pageModel' => $this->mockPageModel('de-CH')]),
            new Route('', ['pageModel' => $this->mockPageModel('en-US')]),
            ['de', 'en'],
            -1,
        ];

        yield 'Sorts route by language (3)' => [
            new Route('', ['pageModel' => $this->mockPageModel('de-CH')]),
            new Route('', ['pageModel' => $this->mockPageModel('en-US')]),
            ['de', 'en-US'],
            -1,
        ];

        yield 'Sorts route by language (4)' => [
            new Route('', ['pageModel' => $this->mockPageModel('de')]),
            new Route('', ['pageModel' => $this->mockPageModel('en-US')]),
            ['en', 'de'],
            1,
        ];

        yield 'Sorts route by language (5)' => [
            new Route('', ['pageModel' => $this->mockPageModel('de')]),
            new Route('', ['pageModel' => $this->mockPageModel('en-US')]),
            ['en', 'de', 'en-US'],
            -1,
        ];

        yield 'Sorts route by language (6)' => [
            new Route('', ['pageModel' => $this->mockPageModel('de-CH')]),
            new Route('', ['pageModel' => $this->mockPageModel('en-US')]),
            ['en-GB', 'de', 'en-US'],
            -1,
        ];

        yield 'Sorts route by language (7)' => [
            new Route('', ['pageModel' => $this->mockPageModel('de-CH')]),
            new Route('', ['pageModel' => $this->mockPageModel('de-DE')]),
            ['de-AT', 'de-CH'],
            -1,
        ];

        yield 'Sorts route by route priority' => [
            new Route('', ['pageModel' => $this->mockPageModel('de', false, false, 128, 1)]),
            new Route('', ['pageModel' => $this->mockPageModel('de', false, false, 128, 10)]),
            ['de', 'de'],
            1,
        ];

        yield 'Sorts route with required parameters first (1)' => [
            new Route('/foo{!parameters}', ['pageModel' => $this->mockPageModel('de')], ['parameters' => '/.+?']),
            new Route('/foo{!parameters}', ['pageModel' => $this->mockPageModel('de')], ['parameters' => '(/.+?)?']),
            ['de', 'de'],
            -1,
        ];

        yield 'Sorts route with required parameters first (2)' => [
            new Route('/foo{!parameters}', ['pageModel' => $this->mockPageModel('de')], ['parameters' => '(/.+?)?']),
            new Route('/foo{!parameters}', ['pageModel' => $this->mockPageModel('de')], ['parameters' => '/.+?']),
            ['de', 'de'],
            1,
        ];

        yield 'Ignores required parameters with equal requirement' => [
            new Route('/foo{!parameters}', ['pageModel' => $this->mockPageModel('de')], ['parameters' => '/.+?']),
            new Route('/foo{!parameters}', ['pageModel' => $this->mockPageModel('de')], ['parameters' => '/.+?']),
            ['de', 'de'],
            0,
        ];

        yield 'Sorts route by root page priority' => [
            new Route('', ['pageModel' => $this->mockPageModel('de-CH', false, false, 256)]),
            new Route('', ['pageModel' => $this->mockPageModel('de-DE', false, false, 100)]),
            ['de', 'en-US'],
            1,
        ];

        yield 'Sorts route lower if it is a root page' => [
            new Route('', ['pageModel' => $this->mockPageModel('en')]),
            new Route('', ['pageModel' => $this->mockPageModel('de', false, true)]),
            null,
            -1,
        ];

        yield 'Sorts route higher if it is not a root page' => [
            new Route('', ['pageModel' => $this->mockPageModel('en', false, true)]),
            new Route('', ['pageModel' => $this->mockPageModel('de')]),
            null,
            1,
        ];

        yield 'Sorting is undefined if both are root page' => [
            new Route('', ['pageModel' => $this->mockPageModel('en', false, true)]),
            new Route('', ['pageModel' => $this->mockPageModel('de', false, true)]),
            null,
            0,
        ];

        yield 'Sorts by number of slashes in path (1)' => [
            new Route('/foo/bar', ['pageModel' => $this->mockPageModel('en')]),
            new Route('/bar', ['pageModel' => $this->mockPageModel('de')]),
            null,
            -1,
        ];

        yield 'Sorts by number of slashes in path (2)' => [
            new Route('/foo', ['pageModel' => $this->mockPageModel('en')]),
            new Route('/bar/foo', ['pageModel' => $this->mockPageModel('de')]),
            null,
            1,
        ];

        yield 'Sorts by number of slashes in path (3)' => [
            new Route('/foo/bar/baz', ['pageModel' => $this->mockPageModel('en')]),
            new Route('/bar/foo/baz/x', ['pageModel' => $this->mockPageModel('de')]),
            null,
            1,
        ];

        yield 'Sorts by path string if it has the same number of slashes' => [
            new Route('/foo/bar/baz', ['pageModel' => $this->mockPageModel('en')]),
            new Route('/bar/foo/baz', ['pageModel' => $this->mockPageModel('de')]),
            null,
            1,
        ];
    }

    /**
     * @dataProvider ordersRoutesByPreferredLanguages
     */
    public function testOrdersRoutesByPreferredLanguages(array $pageLanguages, array $preferredLanguages, array $expected): void
    {
        $instance = $this->getMockForAbstractClass(AbstractPageRouteProvider::class, [], '', false);
        $class = new \ReflectionClass($instance);

        $method = $class->getMethod('convertLanguagesForSorting');
        $preferredLanguages = $method->invoke($instance, $preferredLanguages);

        $method = $class->getMethod('compareRoutes');
        $sorting = 0;

        $routes = array_map(
            fn ($language) => new Route('', ['pageModel' => $this->mockPageModel($language, false, false, ++$sorting)]),
            $pageLanguages,
        );

        usort($routes, static fn ($a, $b) => $method->invoke($instance, $a, $b, $preferredLanguages));

        $result = array_map(static fn (Route $route) => $route->getDefault('pageModel')->rootLanguage, $routes);

        $this->assertSame($expected, $result);
    }

    public static function ordersRoutesByPreferredLanguages(): iterable
    {
        yield [
            ['de', 'en'],
            ['en', 'de'],
            ['en', 'de'],
        ];

        yield [
            ['de_CH', 'fr_CH', 'it_CH'],
            ['it-IT', 'de'],
            ['it_CH', 'de_CH', 'fr_CH'],
        ];

        yield [
            ['en_US', 'de_DE', 'en_GB'],
            ['en', 'de'],
            ['en_US', 'en_GB', 'de_DE'],
        ];

        yield [
            ['en', 'de_DE', 'en_GB'],
            ['en', 'de'],
            ['en', 'en_GB', 'de_DE'],
        ];

        yield [
            ['en_US', 'de_DE', 'fr_FR'],
            ['fr', 'de-CH'],
            ['fr_FR', 'de_DE', 'en_US'],
        ];

        yield [
            ['de_CH', 'fr_CH', 'it_CH'],
            ['de-DE', 'it-CH', 'fr-FR', 'de'],
            ['it_CH', 'fr_CH', 'de_CH'],
        ];

        yield [
            ['de_CH', 'fr_CH', 'de'],
            ['de', 'fr'],
            ['de_CH', 'de', 'fr_CH'],
        ];

        yield 'Correctly handles language tag for a page as well' => [
            ['de-CH', 'fr-CH', 'it-CH'],
            ['it-IT', 'de'],
            ['it-CH', 'de-CH', 'fr-CH'],
        ];

        yield [
            ['zh_Hant_TW', 'zh_Hans_CN', 'de'],
            ['de', 'zh'],
            ['de', 'zh_Hant_TW', 'zh_Hans_CN'],
        ];

        yield [
            ['zh_Hant_TW', 'zh_Hans_CN', 'de'],
            ['de', 'zh_Hans_CN'],
            ['de', 'zh_Hans_CN', 'zh_Hant_TW'],
        ];

        yield 'test' => [
            ['zh_Hant_TW', 'zh_Hans_CN', 'de'],
            ['de', 'zh_Hans'],
            ['de', 'zh_Hans_CN', 'zh_Hant_TW'],
        ];

        yield [
            ['zh_Hant_TW', 'zh_Hans_CN', 'de'],
            ['de', 'zh_CN', 'zh_Hant'],
            ['de', 'zh_Hans_CN', 'zh_Hant_TW'],
        ];

        yield [
            ['zh_Hant_TW', 'zh_Hant', 'de'],
            ['de', 'zh_TW'],
            ['de', 'zh_Hant_TW', 'zh_Hant'],
        ];

        yield [
            ['zh_Hant_TW', 'zh_Hans', 'de'],
            ['de', 'zh_Hans_TW'],
            ['de', 'zh_Hans', 'zh_Hant_TW'],
        ];
    }

    /**
     * @dataProvider convertLanguageForSortingProvider
     */
    public function testConvertLanguagesForSorting(array $languages, array $expected): void
    {
        $instance = $this->getMockForAbstractClass(AbstractPageRouteProvider::class, [], '', false);

        $class = new \ReflectionClass($instance);
        $method = $class->getMethod('convertLanguagesForSorting');
        $result = $method->invoke($instance, $languages);

        $this->assertSame($expected, $result);
    }

    public static function convertLanguageForSortingProvider(): iterable
    {
        yield 'Does nothing on empty array' => [
            [],
            [],
        ];

        yield 'Does not change the sorting' => [
            ['de-DE', 'de-CH', 'fr', 'de', 'en-US', 'en'],
            array_flip(['de_DE', 'de', 'de_CH', 'fr', 'de', 'en_US', 'en', 'en']),
        ];

        yield 'Adds primary language if it does not exist' => [
            ['de-DE'],
            array_flip(['de_DE', 'de']),
        ];

        yield 'Adds primary languages after first region' => [
            ['de_DE', 'de_CH', 'en', 'en_US', 'fr_FR'],
            array_flip(['de_DE', 'de', 'de_CH', 'en', 'en_US', 'fr_FR', 'fr']),
        ];

        yield 'Strips array keys' => [
            ['foo' => 'de', 'bar' => 'en'],
            array_flip(['de', 'en']),
        ];

        yield 'Compiles all fallback locales' => [
            ['zh-Hant-TW', 'zh-Hans-CN'],
            array_flip(['zh_Hant_TW', 'zh_Hant', 'zh_TW', 'zh', 'zh_Hans_CN', 'zh_Hans', 'zh_CN']),
        ];
    }

    private function mockPageModel(string $language, bool $fallback = false, bool $root = false, int $rootSorting = 128, int $routePriority = 0): PageModel&MockObject
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->type = $root ? 'root' : 'regular';
        $pageModel->rootLanguage = $language;
        $pageModel->rootIsFallback = $fallback;
        $pageModel->rootSorting = $rootSorting;
        $pageModel->routePriority = $routePriority;

        return $pageModel;
    }
}
