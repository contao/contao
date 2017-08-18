<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\CoreBundle\Command\VersionCommand;
use PHPUnit\Framework\TestCase;
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
    public function testCanBeInstantiated()
    {
        $command = new VersionCommand('contao:version');

        $this->assertInstanceOf('Contao\CoreBundle\Command\VersionCommand', $command);
        $this->assertSame('contao:version', $command->getName());
    }

    /**
     * Tests that the version number is printed.
     */
    public function testOutputsTheVersionNumber()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.packages', ['contao/core-bundle' => '4.0.2']);

        $command = new VersionCommand('contao:version');
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $code = $tester->execute([]);

        $this->assertSame(0, $code);
        $this->assertContains('4.0.2', $tester->getDisplay());
    }

    /**
     * Tests that the command fails if the version is not set.
     */
    public function testFailsIfVersionNotSet()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.packages', []);

        $command = new VersionCommand('contao:version');
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $code = $tester->execute([]);

        $this->assertSame(1, $code);
        $this->assertSame('', $tester->getDisplay());
    }
}
