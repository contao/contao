<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\Candidates;

use Contao\CoreBundle\Routing\Candidates\LegacyCandidates;
use Contao\CoreBundle\Routing\Candidates\LocaleCandidates;
use Contao\CoreBundle\Routing\Candidates\PageCandidates;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Statement;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;

class CandidatesTest extends TestCase
{
    /**
     * @dataProvider getCandidatesProvider
     */
    public function testGetCandidates(string $pathInfo, array $urlSuffixes, array $languages, array $expected): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getPathinfo')
            ->willReturn($pathInfo)
        ;

        $connection = $this->mockConnectionWithLanguages($languages);

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('getUrlSuffixes')
            ->willReturn($urlSuffixes)
        ;

        $candidates = new PageCandidates($connection, $pageRegistry);

        $this->assertSame($expected['default'], $candidates->getCandidates($request));
    }

    /**
     * @dataProvider getCandidatesProvider
     */
    public function testGetLocaleCandidates(string $pathInfo, array $urlSuffixes, array $languages, array $expected): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getPathinfo')
            ->willReturn($pathInfo)
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('getUrlSuffixes')
            ->willReturn($urlSuffixes)
        ;

        $candidates = new LocaleCandidates($pageRegistry);

        $this->assertSame($expected['locale'] ?? $expected['default'], $candidates->getCandidates($request));
    }

    /**
     * @group legacy
     * @dataProvider getCandidatesProvider
     */
    public function testGetCandidatesInLegacyMode(string $pathInfo, array $urlSuffixes, array $languages, array $expected): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->atLeastOnce())
            ->method('getPathinfo')
            ->willReturn($pathInfo)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $providers = $this->createMock(ServiceLocator::class);
        $providers
            ->expects($this->never())
            ->method($this->anything())
        ;

        $candidates = (new LegacyCandidates('' !== $languages[0], $urlSuffixes[0]))->getCandidates($request);

        $this->assertSame($expected['legacy'] ?? $expected['default'], $candidates);
    }

    public function getCandidatesProvider(): \Generator
    {
        yield [
            '/foo.html',
            ['.html'],
            [''],
            [
                'default' => ['foo'],
            ],
        ];

        yield [
            '/foo.html',
            ['.html', ''],
            [''],
            [
                'default' => ['foo', 'foo.html'],
                'legacy' => ['foo'],
            ],
        ];

        yield [
            '/foo.html',
            ['.html'],
            ['en'],
            [
                'default' => [],
                'locale' => ['foo'],
            ],
        ];

        yield [
            '/foo/bar',
            [''],
            [''],
            [
                'default' => ['foo/bar', 'foo'],
            ],
        ];

        yield [
            '/foo/bar.json',
            ['.html'],
            [''],
            [
                'default' => [],
            ],
        ];

        yield [
            '/foo/bar.html',
            ['.html'],
            [''],
            [
                'default' => ['foo/bar', 'foo'],
            ],
        ];

        yield [
            '/foo/bar.html',
            ['.html', ''],
            [''],
            [
                'default' => ['foo/bar', 'foo', 'foo/bar.html'],
                'legacy' => ['foo/bar', 'foo'],
            ],
        ];

        yield [
            '/foo/bar.html',
            ['.html', '.json'],
            [''],
            [
                'default' => ['foo/bar', 'foo'],
            ],
        ];

        yield [
            '/foo/bar/',
            ['/', '.html'],
            [''],
            [
                'default' => ['foo/bar', 'foo'],
            ],
        ];

        yield [
            '/foo/bar.html',
            ['.html'],
            ['en'],
            [
                'default' => [],
                'locale' => ['foo/bar', 'foo'],
            ],
        ];

        yield [
            '/foo/bar.html',
            ['.html'],
            ['en', ''],
            [
                'default' => ['foo/bar', 'foo'],
                'legacy' => [],
            ],
        ];

        yield [
            '/en/foo/bar.html',
            ['.html'],
            ['en'],
            [
                'default' => ['foo/bar', 'foo'],
                'locale' => ['en/foo/bar', 'en/foo', 'en'],
            ],
        ];

        yield [
            '/en/foo/bar.html',
            ['.html'],
            ['en', ''],
            [
                'default' => ['foo/bar', 'foo', 'en/foo/bar', 'en/foo', 'en'],
                'legacy' => ['foo/bar', 'foo'],
                'locale' => ['en/foo/bar', 'en/foo', 'en'],
            ],
        ];

        yield [
            '/en/foo/bar.html',
            ['.html', ''],
            ['en', ''],
            [
                'default' => ['foo/bar', 'foo', 'foo/bar.html', 'en/foo/bar', 'en/foo', 'en', 'en/foo/bar.html'],
                'legacy' => ['foo/bar', 'foo'],
                'locale' => ['en/foo/bar', 'en/foo', 'en', 'en/foo/bar.html'],
            ],
        ];

        yield [
            '/de-DE/foo/bar.html',
            ['.html'],
            ['en', 'de-DE'],
            [
                'default' => ['foo/bar', 'foo'],
                'locale' => ['de-DE/foo/bar', 'de-DE/foo', 'de-DE'],
            ],
        ];

        yield [
            '/de-DE/foo/bar.html',
            ['.html', ''],
            ['en', 'de-DE', ''],
            [
                'default' => ['foo/bar', 'foo', 'foo/bar.html', 'de-DE/foo/bar', 'de-DE/foo', 'de-DE', 'de-DE/foo/bar.html'],
                'legacy' => ['foo/bar', 'foo'],
                'locale' => ['de-DE/foo/bar', 'de-DE/foo', 'de-DE', 'de-DE/foo/bar.html'],
            ],
        ];

        yield [
            '/de-DE/foo.html',
            ['.html'],
            ['en', 'de-DE'],
            [
                'default' => ['foo'],
                'locale' => ['de-DE/foo', 'de-DE'],
            ],
        ];

        yield [
            '/en/',
            ['.html'],
            ['en'],
            [
                'default' => ['index', '/', 'foobar'],
                'legacy' => [],
                'locale' => [],
            ],
        ];

        yield [
            '/bar.php',
            ['.php'],
            [''],
            [
                'default' => ['bar'],
            ],
        ];

        yield [
            '/de/foo.html',
            ['.html'],
            ['de'],
            [
                'default' => ['foo'],
                'locale' => ['de/foo', 'de'],
            ],
        ];

        yield [
            '/de/foo/bar.html',
            ['.html'],
            ['de'],
            [
                'default' => ['foo/bar', 'foo'],
                'locale' => ['de/foo/bar', 'de/foo', 'de'],
            ],
        ];

        yield [
            '/foo/bar.html',
            ['.html'],
            [''],
            [
                'default' => ['foo/bar', 'foo'],
            ],
        ];

        yield [
            '/foo/bar/baz/some/more.html',
            ['.html'],
            [''],
            [
                'default' => ['foo/bar/baz/some/more', 'foo/bar/baz/some', 'foo/bar/baz', 'foo/bar', 'foo'],
            ],
        ];

        yield [
            '/foo/bar/baz/some/more.html',
            ['.html', ''],
            [''],
            [
                'default' => ['foo/bar/baz/some/more', 'foo/bar/baz/some', 'foo/bar/baz', 'foo/bar', 'foo', 'foo/bar/baz/some/more.html'],
                'legacy' => ['foo/bar/baz/some/more', 'foo/bar/baz/some', 'foo/bar/baz', 'foo/bar', 'foo'],
            ],
        ];

        yield [
            '/de/foo/bar.html',
            ['.html'],
            ['de'],
            [
                'default' => ['foo/bar', 'foo'],
                'locale' => ['de/foo/bar', 'de/foo', 'de'],
            ],
        ];

        yield [
            '/15.html',
            ['.html'],
            [''],
            [
                'default' => ['15'],
            ],
        ];

        yield [
            '/15.html',
            ['.html', ''],
            [''],
            [
                'default' => ['15', '15.html'],
                'legacy' => ['15'],
            ],
        ];

        yield [
            '/de/15.html',
            ['.html'],
            ['de'],
            [
                'default' => ['15'],
                'locale' => ['de/15', 'de'],
            ],
        ];

        yield [
            '/15/foo.html',
            ['.html'],
            [''],
            [
                'default' => ['15/foo', '15'],
            ],
        ];

        yield [
            '/foo/bar/baz.html',
            ['.html'],
            ['foo/bar'],
            [
                'default' => ['baz'],
                'locale' => ['foo/bar/baz', 'foo/bar', 'foo'],
                'legacy' => [],
            ],
        ];
    }

    /**
     * @return Connection&MockObject
     */
    private function mockConnectionWithLanguages(array $languages): Connection
    {
        $prefixStatement = $this->createMock(Statement::class);
        $prefixStatement
            ->expects($this->once())
            ->method('fetchAll')
            ->with(FetchMode::COLUMN)
            ->willReturn($languages)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('query')
            ->with("SELECT DISTINCT urlPrefix FROM tl_page WHERE type='root'")
            ->willReturn($prefixStatement)
        ;

        $rootStatement = $this->createMock(Statement::class);
        $rootStatement
            ->method('fetchAll')
            ->willReturn(['foobar'])
        ;

        $connection
            ->method('executeQuery')
            ->willReturn($rootStatement)
        ;

        return $connection;
    }
}
