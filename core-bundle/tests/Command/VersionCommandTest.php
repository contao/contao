<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Command;

use Contao\CoreBundle\Command\VersionCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the VersionCommand class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class VersionCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $command = new VersionCommand('contao:version');

        $this->assertInstanceOf('Contao\CoreBundle\Command\VersionCommand', $command);
        $this->assertEquals('contao:version', $command->getName());
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
        $code = $tester->execute([]);

        $this->assertEquals(0, $code);
        $this->assertContains('4.0.2', $tester->getDisplay());
    }

    /**
     * Tests the output without the version set.
     */
    public function testOutputWithoutVersion()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.packages', []);

        $command = new VersionCommand('contao:version');
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $code = $tester->execute([]);

        $this->assertEquals(1, $code);
        $this->assertEquals('', $tester->getDisplay());
    }
}
