<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\CoreBundle\Command\UserListCommand;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Model\Collection;
use Contao\UserModel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Tester\CommandTester;

class UserListCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([Table::class, Terminal::class]);

        parent::tearDown();
    }

    public function testDefinition(): void
    {
        $command = $this->getCommand();

        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('column'));
        $this->assertTrue($definition->hasOption('admins'));
    }

    public function testTakesAdminsFlagAsArgument(): void
    {
        $command = $this->getCommand();

        $input = [
            '--admins' => true,
        ];

        $code = (new CommandTester($command))->execute($input);

        $this->assertSame(0, $code);
    }

    public function testReturnsValidJson(): void
    {
        $command = $this->getCommand();

        $input = [
            '--format' => 'json',
        ];

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute($input);
        $output = $commandTester->getDisplay();

        $this->assertSame(0, $code);
        $this->assertNotNull(json_decode($output, true));
    }

    public function testReturnsValidJsonWithSubset(): void
    {
        $command = $this->getCommand();

        $input = [
            '--format' => 'json',
            '--column' => ['name', 'username'],
        ];

        $commandTester = new CommandTester($command);

        $code = $commandTester->execute($input);
        $output = $commandTester->getDisplay();

        $this->assertSame(0, $code);
        $this->assertNotNull(json_decode($output, true));
    }

    public function testTakesColumnAsArgument(): void
    {
        $command = $this->getCommand();

        $input = [
            '--column' => ['username', 'name'],
        ];

        $code = (new CommandTester($command))->execute($input);

        $this->assertSame(0, $code);
    }

    private function getCommand(): UserListCommand
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb
            ->method('select')
            ->willReturnSelf()
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createQueryBuilder')
            ->willReturn($qb)
        ;

        $command = new UserListCommand($connection);
        $command->setApplication(new Application());

        return $command;
    }
}
