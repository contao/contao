<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Api\Command;

use Contao\ManagerBundle\Api\Application;
use Contao\ManagerBundle\Api\Command\VersionCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class VersionCommandTest extends TestCase
{
    public function testInstantiation(): void
    {
        $this->assertInstanceOf('Contao\ManagerBundle\Api\Command\VersionCommand', new VersionCommand());
    }

    public function testHasCorrectName(): void
    {
        $this->assertSame('version', (new VersionCommand())->getName());
    }

    public function testOutputsApiVersion()
    {
        $commandTester = new CommandTester(new VersionCommand());
        $commandTester->execute([]);

        $this->assertSame(Application::VERSION."\n", $commandTester->getDisplay());
    }
}
