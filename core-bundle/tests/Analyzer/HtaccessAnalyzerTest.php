<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Analyzer;

use Contao\CoreBundle\Analyzer\HtaccessAnalyzer;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Tests the HtaccessAnalyzer class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class HtaccessAnalyzerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $file = new SplFileInfo(
            $this->getRootDir().'/system/modules/foobar/assets/.htaccess',
            'system/modules/foobar/assets',
            'system/modules/foobar/assets/.htaccess'
        );

        $htaccess = new HtaccessAnalyzer($file);

        $this->assertInstanceOf('Contao\CoreBundle\Analyzer\HtaccessAnalyzer', $htaccess);
    }

    /**
     * Tests reading the access configuration.
     */
    public function testReadsTheAccessConfigurationFromTheHtaccesFile()
    {
        $file = new SplFileInfo(
            $this->getRootDir().'/system/modules/foobar/assets/.htaccess',
            'system/modules/foobar/assets',
            'system/modules/foobar/assets/.htaccess'
        );

        $htaccess = new HtaccessAnalyzer($file);

        $this->assertTrue($htaccess->grantsAccess());

        $file = new SplFileInfo(
            $this->getRootDir().'/system/modules/foobar/html/.htaccess',
            'system/modules/foobar/html',
            'system/modules/foobar/html/.htaccess'
        );

        $htaccess = new HtaccessAnalyzer($file);

        $this->assertTrue($htaccess->grantsAccess());

        $file = new SplFileInfo(
            $this->getRootDir().'/system/modules/foobar/private/.htaccess',
            'system/modules/foobar/private',
            'system/modules/foobar/private/.htaccess'
        );

        $htaccess = new HtaccessAnalyzer($file);

        $this->assertFalse($htaccess->grantsAccess());
    }

    /**
     * Tests adding an invalid file.
     */
    public function testThrowsAnExceptionIfTheFileIsNotAnHtaccessFile()
    {
        $this->expectException('InvalidArgumentException');

        new HtaccessAnalyzer(new SplFileInfo('iDoNotExist', 'relativePath', 'relativePathName'));
    }
}
