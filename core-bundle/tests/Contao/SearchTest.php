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
     * @param int $expected
     *
     * @dataProvider compareUrlsProvider
     */
    public function testCompareUrls(array $args, $expected)
    {
        $search = new \ReflectionClass(Search::class);
        $compareUrls = $search->getMethod('compareUrls');
        $compareUrls->setAccessible(true);

        if ($expected < 0) {
            $this->assertLessThan(0, $compareUrls->invokeArgs(null, $args));
            $this->assertGreaterThan(0, $compareUrls->invokeArgs(null, array_reverse($args)));
        } elseif ($expected > 0) {
            $this->assertGreaterThan(0, $compareUrls->invokeArgs(null, $args));
            $this->assertLessThan(0, $compareUrls->invokeArgs(null, array_reverse($args)));
        } else {
            $this->assertSame(0, $compareUrls->invokeArgs(null, $args));
            $this->assertSame(0, $compareUrls->invokeArgs(null, array_reverse($args)));
        }
    }

    /**
     * Provides the data for the testCompareUrls() method.
     *
     * @return array
     */
    public function compareUrlsProvider()
    {
        return [
            [['foo/bar.html', 'foo/bar.html?query'], -1],
            [['foo/bar.html', 'foo/bar/baz.html'], -1],
            [['foo/bar.html', 'foo/bar-baz.html'], -1],
            [['foo/bar.html', 'foo/barr.html'], -1],
            [['foo/bar.html', 'foo/baz.html'], -1],
            [['foo/bar.html', 'foo/bar.html'], 0],
            [['foo/bar-longer-url-but-no-query.html', 'foo/bar.html?query'], -1],
            [['foo/bar-longer-url-but-less-slashes.html', 'foo/bar/baz.html'], -1],
            [['foo.html?query/with/many/slashes/', 'foo/bar.html?query-without-slashes'], -1],
        ];
    }
}
