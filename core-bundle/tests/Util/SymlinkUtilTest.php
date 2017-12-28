<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Util;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Util\SymlinkUtil;

/**
 * Tests the SymlinkUtil class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class SymlinkUtilTest extends TestCase
{
    /**
     * Tests an empty source file.
     */
    public function testFailsToCreateTheSymlinkIfTheSourceFileIsEmpty()
    {
        $this->expectException('InvalidArgumentException');

        SymlinkUtil::symlink('', 'target', $this->getRootDir());
    }

    /**
     * Tests an empty target file.
     */
    public function testFailsToCreateTheSymlinkIfTheTargetFileIsEmpty()
    {
        $this->expectException('InvalidArgumentException');

        SymlinkUtil::symlink('source', '', $this->getRootDir());
    }

    /**
     * Tests an invalid target file.
     */
    public function testFailsToCreateTheSymlinkIfTheTargetIsInvalid()
    {
        $this->expectException('InvalidArgumentException');

        SymlinkUtil::symlink('source', '../target', $this->getRootDir());
    }

    /**
     * Tests an existing target file.
     */
    public function testFailsToCreateTheSymlinkIfTheTargetExists()
    {
        $this->expectException('LogicException');

        SymlinkUtil::symlink('source', 'app', $this->getRootDir());
    }
}
