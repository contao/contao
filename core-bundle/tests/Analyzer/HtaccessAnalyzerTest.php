<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Analyzer;

use Contao\CoreBundle\Analyzer\HtaccessAnalyzer;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Finder\SplFileInfo;

class HtaccessAnalyzerTest extends TestCase
{
    public function testReadsTheAccessConfigurationFromTheHtaccesFile(): void
    {
        $file = new SplFileInfo(
            $this->getFixturesDir().'/system/modules/foobar/assets/.htaccess',
            'system/modules/foobar/assets',
            'system/modules/foobar/assets/.htaccess',
        );

        $htaccess = new HtaccessAnalyzer($file);

        $this->assertTrue($htaccess->grantsAccess());

        $file = new SplFileInfo(
            $this->getFixturesDir().'/system/modules/foobar/html/.htaccess',
            'system/modules/foobar/html',
            'system/modules/foobar/html/.htaccess',
        );

        $htaccess = new HtaccessAnalyzer($file);

        $this->assertTrue($htaccess->grantsAccess());

        $file = new SplFileInfo(
            $this->getFixturesDir().'/system/modules/foobar/private/.htaccess',
            'system/modules/foobar/private',
            'system/modules/foobar/private/.htaccess',
        );

        $htaccess = new HtaccessAnalyzer($file);

        $this->assertFalse($htaccess->grantsAccess());
    }

    public function testThrowsAnExceptionIfTheFileIsNotAnHtaccessFile(): void
    {
        $this->expectException('InvalidArgumentException');

        new HtaccessAnalyzer(new SplFileInfo('iDoNotExist', 'relativePath', 'relativePathName'));
    }
}
