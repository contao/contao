<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\HttpKernel\Bundle;

use Contao\CoreBundle\Analyzer\HtaccessAnalyzer;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Tests the HtaccessAnalyzer class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class HtaccessAnalyzerTest extends TestCase
{
    /**
     * @var SplFileInfo
     */
    protected $file;

    /**
     * Creates a file object.
     */
    protected function setUp()
    {
        $this->file = new SplFileInfo(
            $this->getRootDir() . '/system/modules/foobar/html/.htaccess',
            'system/modules/foobar/html',
            'system/modules/foobar/html/.htaccess'
        );
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $htaccess = new HtaccessAnalyzer($this->file);

        $this->assertInstanceOf('Contao\CoreBundle\Analyzer\HtaccessAnalyzer', $htaccess);
    }

    /**
     * Tests the grantsAccess() method.
     */
    public function testGrantsAccess()
    {
        $htaccess = new HtaccessAnalyzer($this->file);

        $this->assertTrue($htaccess->grantsAccess());
    }

    /**
     * Tests adding an invalid file.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidFile()
    {
        new HtaccessAnalyzer(new SplFileInfo('iDoNotExist', 'relativePath', 'relativePathName'));
    }
}
