<?php

declare(strict_types=1);

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

class VersionCommandTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $command = new VersionCommand('contao:version');

        $this->assertInstanceOf('Contao\CoreBundle\Command\VersionCommand', $command);
        $this->assertSame('contao:version', $command->getName());
    }

    public function testOutputsTheVersionNumber(): void
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

    public function testOutputsAnEmptyStringIfTheVersionIsNotSet(): void
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
