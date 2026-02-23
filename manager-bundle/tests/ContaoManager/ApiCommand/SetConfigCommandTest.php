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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SetConfigCommandTest extends TestCase
{
    public function testWritesManagerConfigFromJson(): void
    {
        $config = $this->createMock(ManagerConfig::class);
        $config
            ->expects($this->once())
            ->method('write')
            ->with(['foo' => 'bar'])
        ;

        $application = $this->createStub(Application::class);
        $application
            ->method('getManagerConfig')
            ->willReturn($config)
        ;

        $command = new SetConfigCommand($application);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['json' => '{"foo":"bar"}']);

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testThrowsExceptionWhenJsonIsInvalid(): void
    {
        $application = $this->createStub(Application::class);
        $application
            ->method('getManagerConfig')
            ->willReturn($this->createStub(ManagerConfig::class))
        ;

        $command = new SetConfigCommand($application);

        $this->expectException(\JsonException::class);
        $this->expectExceptionMessage('Syntax error');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['json' => 'foobar']);

        $this->assertSame(0, $commandTester->getStatusCode());
    }
}
