<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\CoreBundle\Tests\TestCase;
use Contao\Search;

/**
 * Tests the Search class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 *
 * @group contao3
 */
class SearchTest extends TestCase
{
    /**
     * @dataProvider compareUrlsProvider
     */
    public function testCompareUrls(string $moreCanonicalUrl, string $lessCanonicalUrl): void
    {
        $search = new \ReflectionClass(Search::class);
        $compareUrls = $search->getMethod('compareUrls');
        $compareUrls->setAccessible(true);

        $this->assertLessThan(0, $compareUrls->invokeArgs(null, [$moreCanonicalUrl, $lessCanonicalUrl]));
        $this->assertGreaterThan(0, $compareUrls->invokeArgs(null, [$lessCanonicalUrl, $moreCanonicalUrl]));
        $this->assertSame(0, $compareUrls->invokeArgs(null, [$moreCanonicalUrl, $moreCanonicalUrl]));
        $this->assertSame(0, $compareUrls->invokeArgs(null, [$lessCanonicalUrl, $lessCanonicalUrl]));
    }

    public function compareUrlsProvider(): \Generator
    {
        yield ['foo/bar.html', 'foo/bar.html?query'];
        yield ['foo/bar.html', 'foo/bar/baz.html'];
        yield ['foo/bar.html', 'foo/bar-baz.html'];
        yield ['foo/bar.html', 'foo/barr.html'];
        yield ['foo/bar.html', 'foo/baz.html'];
        yield ['foo/bar-longer-url-but-no-query.html', 'foo/bar.html?query'];
        yield ['foo/bar-longer-url-but-less-slashes.html', 'foo/bar/baz.html'];
        yield ['foo.html?query/with/many/slashes/', 'foo/bar.html?query-without-slashes'];
    }
}
