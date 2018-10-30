<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\CoreBundle\Command\VersionCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests the VersionCommand class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class VersionCommandTest extends TestCase
{
    /**
     * Tests printing the version number.
     */
    public function testOutputsTheVersionNumber()
    {
        $tester = new CommandTester(new VersionCommand(['contao/core-bundle' => '4.0.2']));
        $code = $tester->execute([]);

        $this->assertSame(0, $code);
        $this->assertContains('4.0.2', $tester->getDisplay());
    }
}
