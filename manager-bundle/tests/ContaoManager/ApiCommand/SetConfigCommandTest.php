<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\ContaoManager\ApiCommand;

use Contao\ManagerBundle\Api\Application;
use Contao\ManagerBundle\Api\ManagerConfig;
use Contao\ManagerBundle\ContaoManager\ApiCommand\SetConfigCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SetConfigCommandTest extends TestCase
{
    private ManagerConfig&MockObject $config;

    private SetConfigCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(ManagerConfig::class);

        $application = $this->createMock(Application::class);
        $application
            ->method('getManagerConfig')
            ->willReturn($this->config)
        ;

        $this->command = new SetConfigCommand($application);
    }

    public function testHasCorrectNameAndArguments(): void
    {
        $this->assertSame('config:set', $this->command->getName());
        $this->assertTrue($this->command->getDefinition()->hasArgument('json'));
        $this->assertTrue($this->command->getDefinition()->getArgument('json')->isRequired());
    }

    public function testWritesManagerConfigFromJson(): void
    {
        $this->config
            ->expects($this->once())
            ->method('write')
            ->with(['foo' => 'bar'])
        ;

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['json' => '{"foo":"bar"}']);

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testThrowsExceptionWhenJsonIsInvalid(): void
    {
        $this->expectException(\JsonException::class);
        $this->expectExceptionMessage('Syntax error');

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['json' => 'foobar']);

        $this->assertSame(0, $commandTester->getStatusCode());
    }
}
