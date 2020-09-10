<?php

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
     * @param string $moreCanonicalUrl
     * @param string $lessCanonicalUrl
     *
     * @dataProvider compareUrlsProvider
     */
    public function testCompareUrls($moreCanonicalUrl, $lessCanonicalUrl)
    {
        $search = new \ReflectionClass(Search::class);
        $compareUrls = $search->getMethod('compareUrls');
        $compareUrls->setAccessible(true);

        $this->assertLessThan(0, $compareUrls->invokeArgs(null, [$moreCanonicalUrl, $lessCanonicalUrl]));
        $this->assertGreaterThan(0, $compareUrls->invokeArgs(null, [$lessCanonicalUrl, $moreCanonicalUrl]));
        $this->assertSame(0, $compareUrls->invokeArgs(null, [$moreCanonicalUrl, $moreCanonicalUrl]));
        $this->assertSame(0, $compareUrls->invokeArgs(null, [$lessCanonicalUrl, $lessCanonicalUrl]));
    }

    /**
     * Provides the data for the testCompareUrls() method.
     *
     * @return array
     */
    public function compareUrlsProvider()
    {
        return [
            ['foo/bar.html', 'foo/bar.html?query'],
            ['foo/bar.html', 'foo/bar/baz.html'],
            ['foo/bar.html', 'foo/bar-baz.html'],
            ['foo/bar.html', 'foo/barr.html'],
            ['foo/bar.html', 'foo/baz.html'],
            ['foo/bar-longer-url-but-no-query.html', 'foo/bar.html?query'],
            ['foo/bar-longer-url-but-less-slashes.html', 'foo/bar/baz.html'],
            ['foo.html?query/with/many/slashes/', 'foo/bar.html?query-without-slashes'],
        ];
    }
}
