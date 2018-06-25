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

use Contao\ManagerBundle\Api\Command\GetConfigCommand;
use Contao\ManagerBundle\Api\ManagerConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class GetConfigCommandTest extends TestCase
{
    /**
     * @var ManagerConfig|MockObject
     */
    private $config;

    /**
     * @var GetConfigCommand
     */
    private $command;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->config = $this->createMock(ManagerConfig::class);
        $this->command = new GetConfigCommand($this->config);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf('Contao\ManagerBundle\Api\Command\GetConfigCommand', $this->command);
    }

    public function testHasCorrectName(): void
    {
        $this->assertSame('config:get', $this->command->getName());
    }

    public function testDumpsManagerConfigAsJSON()
    {
        $this->config
            ->expects($this->once())
            ->method('all')
            ->willReturn(['foo' => 'bar'])
        ;

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $this->assertSame('{"foo":"bar"}'."\n", $commandTester->getDisplay());
    }
}
