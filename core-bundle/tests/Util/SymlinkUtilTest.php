<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Util;

use Contao\CoreBundle\Test\TestCase;
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
     *
     * @expectedException \InvalidArgumentException
     */
    public function testEmptySource()
    {
        SymlinkUtil::symlink('', 'target', $this->getRootDir());
    }

    /**
     * Tests an empty target file.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testEmptyTarget()
    {
        SymlinkUtil::symlink('source', '', $this->getRootDir());
    }

    /**
     * Tests an invalid target file.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidTarget()
    {
        SymlinkUtil::symlink('source', '../target', $this->getRootDir());
    }

    /**
     * Tests an existing target file.
     *
     * @expectedException \LogicException
     */
    public function testExistingTarget()
    {
        SymlinkUtil::symlink('source', 'app', $this->getRootDir());
    }
}
