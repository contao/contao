<?php

declare(strict_types=1);

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

class SymlinkUtilTest extends TestCase
{
    public function testFailsToCreateTheSymlinkIfTheSourceFileIsEmpty(): void
    {
        $this->expectException('InvalidArgumentException');

        SymlinkUtil::symlink('', 'target', $this->getFixturesDir());
    }

    public function testFailsToCreateTheSymlinkIfTheTargetFileIsEmpty(): void
    {
        $this->expectException('InvalidArgumentException');

        SymlinkUtil::symlink('source', '', $this->getFixturesDir());
    }

    public function testFailsToCreateTheSymlinkIfTheTargetIsInvalid(): void
    {
        $this->expectException('InvalidArgumentException');

        SymlinkUtil::symlink('source', '../target', $this->getFixturesDir());
    }

    public function testFailsToCreateTheSymlinkIfTheTargetExists(): void
    {
        $this->expectException('LogicException');

        SymlinkUtil::symlink('source', 'app', $this->getFixturesDir());
    }
}
