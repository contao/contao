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

use Contao\CoreBundle\Routing\Candidates\LocaleCandidates;
use Contao\CoreBundle\Routing\Candidates\PageCandidates;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;

class PageCandidatesTest extends TestCase
{
    /**
     * @dataProvider getCandidatesProvider
     */
    public function testGetCandidates(string $pathInfo, array $urlSuffixes, array $languages, array $expected): void
    {
        $request = $this->mockRequest($pathInfo);
        $connection = $this->mockConnection();
        $pageRegistry = $this->mockPageRegistry($languages, $urlSuffixes);
        $candidates = new PageCandidates($connection, $pageRegistry);

        $this->assertSame($expected['default'], $candidates->getCandidates($request));
    }

    /**
     * @dataProvider getCandidatesProvider
     */
    public function testGetLocaleCandidates(string $pathInfo, array $urlSuffixes, array $languages, array $expected): void
    {
        $request = $this->mockRequest($pathInfo);
        $pageRegistry = $this->mockPageRegistry(null, $urlSuffixes);
        $candidates = new LocaleCandidates($pageRegistry);

        $this->assertSame($expected['locale'] ?? $expected['default'], $candidates->getCandidates($request));
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
                'default' => ['index', '/', 15],
                'legacy' => [],
                'locale' => [],
            ],
        ];

        yield [
            '/en',
            ['.html'],
            ['en'],
            [
                'default' => ['index', '/', 15],
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

        // Ensure that 0 does not trigger the "AbstractCandidates::getCandidates cannot handle empty path" exception
        yield [
            '/0',
            ['.html'],
            [''],
            [
                'default' => [],
            ],
        ];
    }

    public function testIncluesPageWithAbsolutePath(): void
    {
        $request = $this->mockRequest('/foo/bar/baz.html');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects($this->once())
            ->method('orWhere')
            ->with('type IN (:types)')
            ->willReturnSelf()
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('types', ['foo', 'bar'], ArrayParameterType::STRING)
            ->willReturnSelf()
        ;

        $pageRegistry = $this->mockPageRegistry(['foo', 'bar'], ['.html']);
        $pageRegistry
            ->expects($this->once())
            ->method('getPathRegex')
            ->willReturn([
                'foo' => '#^/bar/[a-z]+$#sD',
                'bar' => '#^/bar$#sD',
            ])
        ;

        $candidates = new PageCandidates($this->mockConnection($queryBuilder), $pageRegistry);

        $this->assertSame(['bar/baz', 'bar', 15], $candidates->getCandidates($request));
    }

    public function testIncluesPageWithAbsolutePathAndSuffix(): void
    {
        $request = $this->mockRequest('/foo/bar/baz.html');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects($this->once())
            ->method('orWhere')
            ->with('type IN (:types)')
            ->willReturnSelf()
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('types', ['foo', 'bar'], ArrayParameterType::STRING)
            ->willReturnSelf()
        ;

        $pageRegistry = $this->mockPageRegistry(['foo', 'bar'], ['.html', '']);
        $pageRegistry
            ->expects($this->once())
            ->method('getPathRegex')
            ->willReturn([
                'foo' => '#^/bar/[a-z]+\.html$#sD',
                'bar' => '#^/bar$#sD',
            ])
        ;

        $candidates = new PageCandidates($this->mockConnection($queryBuilder), $pageRegistry);

        $this->assertSame(['bar/baz', 'bar', 'bar/baz.html', 15], $candidates->getCandidates($request));
    }

    public function testIncluesPageWithAbsolutePathWithoutPrefix(): void
    {
        $request = $this->mockRequest('/bar/baz.html');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects($this->once())
            ->method('orWhere')
            ->with('type IN (:types)')
            ->willReturnSelf()
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('types', ['foo', 'bar'], ArrayParameterType::STRING)
            ->willReturnSelf()
        ;

        $pageRegistry = $this->mockPageRegistry(['foo', ''], ['.html']);
        $pageRegistry
            ->expects($this->once())
            ->method('getPathRegex')
            ->willReturn([
                'foo' => '#^/bar/[a-z]+$#sD',
                'bar' => '#^/bar$#sD',
            ])
        ;

        $candidates = new PageCandidates($this->mockConnection($queryBuilder), $pageRegistry);

        $this->assertSame(['bar/baz', 'bar', 15], $candidates->getCandidates($request));
    }

    private function mockRequest(string $pathInfo): Request&MockObject
    {
        $request = $this->createMock(Request::class);
        $request
            ->method('getHttpHost')
            ->willReturn('www.example.com')
        ;

        $request
            ->expects($this->atLeastOnce())
            ->method('getPathinfo')
            ->willReturn($pathInfo)
        ;

        return $request;
    }

    private function mockPageRegistry(array|null $urlPrefixes, array|null $urlSuffixes): PageRegistry&MockObject
    {
        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects(null === $urlPrefixes ? $this->never() : $this->once())
            ->method('getUrlPrefixes')
            ->willReturn($urlPrefixes ?: [])
        ;

        $pageRegistry
            ->expects(null === $urlSuffixes ? $this->never() : $this->once())
            ->method('getUrlSuffixes')
            ->willReturn($urlSuffixes ?: [])
        ;

        return $pageRegistry;
    }

    /**
     * @param QueryBuilder&MockObject $queryBuilder
     */
    private function mockConnection(QueryBuilder|null $queryBuilder = null): Connection&MockObject
    {
        $queryBuilder ??= $this->createMock(QueryBuilder::class);

        $result = $this->createMock(Result::class);
        $result
            ->method('fetchFirstColumn')
            ->willReturn([15])
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('select')
            ->with('id')
            ->willReturnSelf()
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('from')
            ->with('tl_page')
            ->willReturnSelf()
        ;

        $queryBuilder
            ->method('executeQuery')
            ->willReturn($result)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder)
        ;

        return $connection;
    }
}
