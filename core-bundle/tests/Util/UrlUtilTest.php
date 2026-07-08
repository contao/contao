<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Util;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Util\UrlUtil;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\UriInterface;

class UrlUtilTest extends TestCase
{
    #[DataProvider('getMakeAbsolute')]
    public function testMakeAbsolute(string $url, string $base, string $expected): void
    {
        $this->assertSame($expected, UrlUtil::makeAbsolute($url, $base));
    }

    public static function getMakeAbsolute(): iterable
    {
        $fullUrl = 'https://user:pass@host:123/path/file?query#fragment';

        yield ['', $fullUrl, 'https://user:pass@host:123/path/file?query'];
        yield ['#foo', $fullUrl, 'https://user:pass@host:123/path/file?query#foo'];
        yield ['?foo', $fullUrl, 'https://user:pass@host:123/path/file?foo'];
        yield ['?foo#bar', $fullUrl, 'https://user:pass@host:123/path/file?foo#bar'];
        yield ['/foo', $fullUrl, 'https://user:pass@host:123/foo'];
        yield ['/foo?bar', $fullUrl, 'https://user:pass@host:123/foo?bar'];
        yield ['/foo?bar#baz', $fullUrl, 'https://user:pass@host:123/foo?bar#baz'];
        yield ['foo', $fullUrl, 'https://user:pass@host:123/path/foo'];
        yield ['foo?bar', $fullUrl, 'https://user:pass@host:123/path/foo?bar'];
        yield ['foo?bar#baz', $fullUrl, 'https://user:pass@host:123/path/foo?bar#baz'];
        yield ['./foo', $fullUrl, 'https://user:pass@host:123/path/foo'];
        yield ['./foo/../bar', $fullUrl, 'https://user:pass@host:123/path/bar'];
        yield ['../foo', $fullUrl, 'https://user:pass@host:123/foo'];
        yield ['https://example.com', $fullUrl, 'https://example.com/'];
        yield ['http://example.com', $fullUrl, 'http://example.com/'];
        yield ['//example.com', $fullUrl, 'https://example.com/'];
        yield ['foo', 'https://example.com/path/file/', 'https://example.com/path/file/foo'];
        yield ['../foo', 'https://example.com/', 'https://example.com/foo'];
        yield ['../foo', 'https://example.com', 'https://example.com/foo'];
        yield ['foo', '//example.com/path/file', '//example.com/path/foo'];
        yield ['foo', '/c413/public/', '/c413/public/foo'];
        yield ['../foo', '/c413/public/', '/c413/foo'];
        yield ['?foo', '/c413/public/', '/c413/public/?foo'];
        yield ['#foo', '/c413/public/', '/c413/public/#foo'];
        yield ['foo', '', '/foo'];
        yield ['../foo', '', '/foo'];
        yield ['?foo', '', '/?foo'];
        yield ['#foo', '', '/#foo'];
        yield ['HTTPS://example.com', '', 'https://example.com/'];
        yield ['', 'HTTPS://example.com', 'https://example.com/'];
        yield ['mailto:mail@example.com?subject=hi', '', 'mailto:mail@example.com?subject=hi'];
        yield ['tel:+1234-56789', '', 'tel:+1234-56789'];
        yield ['data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', '', 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'];
    }

    public function testFailsForRelativeBasePath(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        UrlUtil::makeAbsolute('foo', 'path/foo');
    }

    public function testHandlesPunycodeDomains(): void
    {
        $this->assertSame('https://fööbar.de/foo', UrlUtil::makeAbsolute('/foo', 'https://fööbar.de/'));
        $this->assertSame('https://xn--fbar-5qaa.de/foo', UrlUtil::makeAbsolute('/foo', 'https://xn--fbar-5qaa.de/'));
    }

    #[DataProvider('normalizePathAndQueryProvider')]
    public function testNormalizePathAndQuery(string $url, string $expected): void
    {
        $actual = UrlUtil::getNormalizePathAndQuery($url);

        $this->assertSame($expected, $actual);
    }

    public static function normalizePathAndQueryProvider(): iterable
    {
        yield [
            '/foo/bar',
            '/foo/bar',
        ];

        yield [
            '/foo/bar?bar=baz',
            '/foo/bar?bar=baz',
        ];

        yield [
            '/foo/bar?foo=bar&bar=baz',
            '/foo/bar?bar=baz&foo=bar',
        ];

        yield [
            '/foo/bar?foo=bar&rt=1234',
            '/foo/bar?foo=bar',
        ];

        yield [
            '/foo/bar?foo=bar&ref=1234',
            '/foo/bar?foo=bar',
        ];

        yield [
            '/foo/bar?foo=bar&revise=1234',
            '/foo/bar?foo=bar',
        ];

        yield [
            '/foo/bar?bar=baz&rt=1&ref=2&revise=3',
            '/foo/bar?bar=baz',
        ];
    }

    #[DataProvider('mergeQueryIfMissingProvider')]
    public function testMergeQueryIfMissing(UriInterface|string $uri, string $queryToAdd, string $expected): void
    {
        $actual = UrlUtil::mergeQueryIfMissing($uri, $queryToAdd);
        $this->assertSame($expected, (string) $actual);
    }

    public static function mergeQueryIfMissingProvider(): iterable
    {
        yield 'Adds query to url without query' => [
            'https://example.com/path',
            'a=1',
            'https://example.com/path?a=1',
        ];

        yield 'Strips leading question mark' => [
            'https://example.com/path',
            '?a=1',
            'https://example.com/path?a=1',
        ];

        yield 'Strips leading ampersand' => [
            'https://example.com/path',
            '&a=1',
            'https://example.com/path?a=1',
        ];

        yield 'Empty queryToAdd does not change url (empty string)' => [
            'https://example.com/path?a=1',
            '',
            'https://example.com/path?a=1',
        ];

        yield 'Empty queryToAdd does not change url (just ?)' => [
            'https://example.com/path?a=1',
            '?',
            'https://example.com/path?a=1',
        ];

        yield 'Empty queryToAdd does not change url (just &)' => [
            'https://example.com/path?a=1',
            '&',
            'https://example.com/path?a=1',
        ];

        yield 'Adds only missing keys' => [
            'https://example.com/path?a=1',
            'a=999&b=2',
            'https://example.com/path?a=1&b=2',
        ];

        yield 'Adds multiple keys when none exist' => [
            'https://example.com/path',
            'a=1&b=2',
            'https://example.com/path?a=1&b=2',
        ];

        yield 'Keeps existing key order and appends new keys' => [
            'https://example.com/path?b=2&a=1',
            'c=3&a=999',
            'https://example.com/path?b=2&a=1&c=3',
        ];

        yield 'Accepts UriInterface input' => [
            new Uri('https://example.com/path?a=1'),
            'b=2',
            'https://example.com/path?a=1&b=2',
        ];

        yield 'Preserves fragment' => [
            'https://example.com/path?a=1#fragment',
            'b=2',
            'https://example.com/path?a=1&b=2#fragment',
        ];

        yield 'Uses RFC3986 encoding' => [
            'https://example.com/path',
            'q=hello world',
            'https://example.com/path?q=hello%20world',
        ];

        yield 'No query remains no query' => [
            'https://example.com/path',
            '',
            'https://example.com/path',
        ];
    }
}
