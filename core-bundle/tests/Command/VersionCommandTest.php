<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Command;

use Contao\CoreBundle\Command\VersionCommand;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests the VersionCommand class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class VersionCommandTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $command = new VersionCommand('contao:version');

        $this->assertInstanceOf('Contao\CoreBundle\Command\VersionCommand', $command);
    }

    /**
     * Tests the output.
     */
    public function testOutput()
    {
        define('VERSION', '4.0');
        define('BUILD', '2');

        $command = new VersionCommand('contao:version');
        $tester  = new CommandTester($command);

        $tester->execute([]);

        $this->assertEquals("4.0.2\n", $tester->getDisplay());
    }
}
