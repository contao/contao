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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Candidates\Candidates;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Symfony\Component\HttpFoundation\Request;

class CandidatesTest extends TestCase
{
    /**
     * @dataProvider getCandidatesProvider
     */
    public function testGetCandidates(string $pathInfo, array $urlSuffix, array $languages, array $expected): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getPathinfo')
            ->willReturn($pathInfo)
        ;

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->atLeastOnce())
            ->method('isLegacyRouting')
            ->willReturn(false)
        ;

        $connection = $this->mockConnectionWithConfig($urlSuffix, $languages);

        $candidates = (new Candidates($framework, $connection, '.html', false))->getCandidates($request);

        $this->assertSame($expected, $candidates);
    }

    /**
     * @dataProvider getCandidatesProvider
     */
    public function testGetCandidatesInLegacyMode(string $pathInfo, array $urlSuffix, array $languages, array $expectedRegular, array $expected): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getPathinfo')
            ->willReturn($pathInfo)
        ;

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->atLeastOnce())
            ->method('isLegacyRouting')
            ->willReturn(true)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $candidates = (new Candidates($framework, $connection, $urlSuffix[0] ?? '', 0 !== \count($languages)))->getCandidates($request);

        $this->assertSame($expected, $candidates);
    }

    public function getCandidatesProvider()
    {
        yield [
            '/',
            [],
            [],
            ['index'],
            ['index'],
        ];

        yield [
            '/',
            ['.html'],
            [],
            ['index'],
            [],
        ];

        yield [
            '/',
            [],
            ['en'],
            ['index'],
            [],
        ];

        yield [
            '/foo.html',
            ['.html'],
            [],
            ['foo.html', 'foo'],
            ['foo'],
        ];

        yield [
            '/foo.html',
            ['.html'],
            ['en'],
            ['foo.html', 'foo'],
            [],
        ];

        yield [
            '/foo/bar',
            [],
            [],
            ['foo/bar', 'foo'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/foo/bar.json',
            ['.html'],
            [],
            ['foo/bar.json', 'foo'],
            [],
        ];

        yield [
            '/foo/bar.html',
            ['.html'],
            [],
            ['foo/bar.html', 'foo', 'foo/bar'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/foo/bar.html',
            ['.html', '.json'],
            [],
            ['foo/bar.html', 'foo', 'foo/bar'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/foo/bar/',
            ['/', '.html'],
            [],
            ['foo/bar/', 'foo/bar', 'foo'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/foo/bar.html',
            ['.html'],
            ['en'],
            ['foo/bar.html', 'foo', 'foo/bar'],
            [],
        ];

        yield [
            '/en/foo/bar.html',
            ['.html'],
            ['en'],
            ['en/foo/bar.html', 'en/foo', 'en', 'en/foo/bar', 'foo/bar.html', 'foo', 'foo/bar'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/de-DE/foo/bar.html',
            ['.html'],
            ['en', 'de-DE'],
            ['de-DE/foo/bar.html', 'de-DE/foo', 'de-DE', 'de-DE/foo/bar', 'foo/bar.html', 'foo', 'foo/bar'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/de-DE/foo.html',
            ['.html'],
            ['en', 'de-DE'],
            ['de-DE/foo.html', 'de-DE', 'de-DE/foo', 'foo.html', 'foo'],
            ['foo'],
        ];

        yield [
            '/en/',
            ['.html'],
            ['en'],
            ['en/', 'en', 'index'],
            [],
        ];

        yield [
            '/bar.php',
            ['.php'],
            [],
            ['bar.php', 'bar'],
            ['bar'],
        ];

        yield [
            '/de/foo.html',
            ['.html'],
            ['de'],
            ['de/foo.html', 'de', 'de/foo', 'foo.html', 'foo'],
            ['foo'],
        ];

        yield [
            '/de/foo/bar.html',
            ['.html'],
            ['de'],
            ['de/foo/bar.html', 'de/foo', 'de', 'de/foo/bar', 'foo/bar.html', 'foo', 'foo/bar'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/foo/bar.html',
            ['.html'],
            [],
            ['foo/bar.html', 'foo', 'foo/bar'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/foo/bar/baz/some/more.html',
            ['.html'],
            [],
            ['foo/bar/baz/some/more.html', 'foo/bar/baz/some', 'foo/bar/baz', 'foo/bar', 'foo', 'foo/bar/baz/some/more'],
            ['foo/bar/baz/some/more', 'foo/bar/baz/some', 'foo/bar/baz', 'foo/bar', 'foo'],
        ];

        yield [
            '/de/foo/bar.html',
            ['.html'],
            ['de'],
            ['de/foo/bar.html', 'de/foo', 'de', 'de/foo/bar', 'foo/bar.html', 'foo', 'foo/bar'],
            ['foo/bar', 'foo'],
        ];

        yield [
            '/15.html',
            ['.html'],
            [],
            ['15.html', '15'],
            ['15'],
        ];

        yield [
            '/de/15.html',
            ['.html'],
            ['de'],
            ['de/15.html', 'de', 'de/15', '15.html', '15'],
            ['15'],
        ];

        yield [
            '/15/foo.html',
            ['.html'],
            [],
            ['15/foo.html', '15', '15/foo'],
            ['15/foo', '15'],
        ];
    }

    private function mockConnectionWithConfig(array $urlSuffix, array $languages)
    {
        $rows = [];

        foreach ($urlSuffix as $suffix) {
            $language = array_shift($languages);
            $rows[] = ['urlSuffix' => $suffix, 'languagePrefix' => (string) $language];
        }

        foreach ($languages as $language) {
            $rows[] = ['urlSuffix' => '', 'languagePrefix' => $language];
        }

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn($rows)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('query')
            ->with('SELECT urlSuffix, languagePrefix FROM tl_page')
            ->willReturn($statement)
        ;

        return $connection;
    }
}
