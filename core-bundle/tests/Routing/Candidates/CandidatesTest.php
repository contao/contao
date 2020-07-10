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

use Contao\CoreBundle\Routing\Candidates\Candidates;
use Contao\CoreBundle\Routing\Candidates\LegacyCandidates;
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

        $candidates = new Candidates($connection, $pageRegistry);

        $this->assertSame($expected, $candidates->getCandidates($request));
    }

    /**
     * @dataProvider getCandidatesProvider
     *
     * @group legacy
     */
    public function testGetCandidatesInLegacyMode(string $pathInfo, array $urlSuffixes, array $languages, array $expectedRegular, array $expected): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
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

        $this->assertSame($expected, $candidates);
    }

    public function getCandidatesProvider()
    {
        yield [
            '/foo.html',
            ['.html'],
            [''],
            ['foo'],
            ['foo'],
        ];

        yield [
            '/foo.html',
            ['.html', ''],
            [''],
            ['foo', 'foo.html'],
            ['foo'],
        ];

        yield [
            '/foo.html',
            ['.html'],
            ['en'],
            [],
            [],
        ];

        yield [
            '/foo/bar',
            [''],
            [''],
            ['foo/bar', 'foo'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/foo/bar.json',
            ['.html'],
            [''],
            [],
            [],
        ];

        yield [
            '/foo/bar.html',
            ['.html'],
            [''],
            ['foo/bar', 'foo'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/foo/bar.html',
            ['.html', ''],
            [''],
            ['foo/bar', 'foo', 'foo/bar.html'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/foo/bar.html',
            ['.html', '.json'],
            [''],
            ['foo/bar', 'foo'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/foo/bar/',
            ['/', '.html'],
            [''],
            ['foo/bar', 'foo'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/foo/bar.html',
            ['.html'],
            ['en'],
            [],
            [],
        ];

        yield [
            '/foo/bar.html',
            ['.html'],
            ['en', ''],
            ['foo/bar', 'foo'],
            [],
        ];

        yield [
            '/en/foo/bar.html',
            ['.html'],
            ['en'],
            ['foo/bar', 'foo'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/en/foo/bar.html',
            ['.html'],
            ['en', ''],
            ['foo/bar', 'foo', 'en/foo/bar', 'en/foo', 'en'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/en/foo/bar.html',
            ['.html', ''],
            ['en', ''],
            ['foo/bar', 'foo', 'foo/bar.html', 'en/foo/bar', 'en/foo', 'en', 'en/foo/bar.html'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/de-DE/foo/bar.html',
            ['.html'],
            ['en', 'de-DE'],
            ['foo/bar', 'foo'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/de-DE/foo/bar.html',
            ['.html', ''],
            ['en', 'de-DE', ''],
            ['foo/bar', 'foo', 'foo/bar.html', 'de-DE/foo/bar', 'de-DE/foo', 'de-DE', 'de-DE/foo/bar.html'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/de-DE/foo.html',
            ['.html'],
            ['en', 'de-DE'],
            ['foo'],
            ['foo'],
        ];

        yield [
            '/en/',
            ['.html'],
            ['en'],
            ['index'],
            [],
        ];

        yield [
            '/bar.php',
            ['.php'],
            [''],
            ['bar'],
            ['bar'],
        ];

        yield [
            '/de/foo.html',
            ['.html'],
            ['de'],
            ['foo'],
            ['foo'],
        ];

        yield [
            '/de/foo/bar.html',
            ['.html'],
            ['de'],
            ['foo/bar', 'foo'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/foo/bar.html',
            ['.html'],
            [''],
            ['foo/bar', 'foo'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/foo/bar/baz/some/more.html',
            ['.html'],
            [''],
            ['foo/bar/baz/some/more', 'foo/bar/baz/some', 'foo/bar/baz', 'foo/bar', 'foo'],
            ['foo/bar/baz/some/more', 'foo/bar/baz/some', 'foo/bar/baz', 'foo/bar', 'foo'],
        ];

        yield [
            '/foo/bar/baz/some/more.html',
            ['.html', ''],
            [''],
            ['foo/bar/baz/some/more', 'foo/bar/baz/some', 'foo/bar/baz', 'foo/bar', 'foo', 'foo/bar/baz/some/more.html'],
            ['foo/bar/baz/some/more', 'foo/bar/baz/some', 'foo/bar/baz', 'foo/bar', 'foo'],
        ];

        yield [
            '/de/foo/bar.html',
            ['.html'],
            ['de'],
            ['foo/bar', 'foo'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/15.html',
            ['.html'],
            [''],
            ['15'],
            ['15'],
        ];

        yield [
            '/15.html',
            ['.html', ''],
            [''],
            ['15', '15.html'],
            ['15'],
        ];

        yield [
            '/de/15.html',
            ['.html'],
            ['de'],
            ['15'],
            ['15'],
        ];

        yield [
            '/15/foo.html',
            ['.html'],
            [''],
            ['15/foo', '15'],
            ['15/foo', '15'],
        ];
    }

    /**
     * @return Connection&MockObject
     */
    private function mockConnectionWithLanguages(array $languages): Connection
    {
        $statement = $this->createMock(Statement::class);
        $statement
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
            ->willReturn($statement)
        ;

        return $connection;
    }
}
