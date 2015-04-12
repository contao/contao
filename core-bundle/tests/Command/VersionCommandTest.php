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
use Symfony\Component\DependencyInjection\ContainerBuilder;

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

        $this->assertInstanceOf('Contao\\CoreBundle\\Command\\VersionCommand', $command);
    }

    /**
     * Tests the output.
     */
    public function testOutput()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.packages', ['contao/core-bundle' => '4.0.2']);

        $command = new VersionCommand('contao:version');
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertEquals("4.0.2\n", $tester->getDisplay());
    }
}
