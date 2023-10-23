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
        $command = $this->getCommand($this->createMock(QueryBuilder::class));

        $this->assertNotEmpty($command->getDescription());

        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('column'));
        $this->assertTrue($definition->hasOption('admins'));
    }

    public function testThrowsExceptionOnInvalidFormat(): void
    {
        $command = $this->getCommand($this->mockQueryBuilder([]));

        $this->expectException(\LogicException::class);
        $this->executeCommand($command, ['--format' => 'foo'], 1);
    }

    /**
     * @dataProvider listsUsersProvider
     */
    public function testListsUsers(array $input, array $data, string $expected): void
    {
        $command = $this->getCommand($this->mockQueryBuilder($data));
        $output = $this->executeCommand($command, $input);

        $this->assertStringContainsString($expected, $output);
    }

    /**
     * @dataProvider listsUsersProvider
     */
    public function testListsUsersAsJson(array $input, array $data, string $expectedTxt, array $expected): void
    {
        $input['--format'] = 'json';

        $command = $this->getCommand($this->mockQueryBuilder($data));
        $output = json_decode($this->executeCommand($command, $input), true);

        $this->assertSame($expected, $output);
    }

    public function listsUsersProvider(): \Generator
    {
        yield 'Returns empty result' => [
            [],
            [],
            'No accounts found.',
            [],
        ];

        yield 'Returns empty result with column argument' => [
            ['--column' => ['firstname', 'lastname']],
            [],
            'No accounts found.',
            [],
        ];

        yield 'Returns default fields from data' => [
            [],
            [['id' => 42, 'username' => 'k.jones', 'name' => 'Kevin Jones', 'admin' => '1', 'dateAdded' => 1697459597, 'lastLogin' => 1697459597]],
            'k.jones    Kevin Jones',
            [['username' => 'k.jones', 'name' => 'Kevin Jones', 'admin' => '1', 'dateAdded' => 1697459597, 'lastLogin' => 1697459597]],
        ];

        yield 'Returns custom columns' => [
            ['--column' => ['id', 'username']],
            [['id' => 42, 'username' => 'k.jones', 'name' => 'Kevin Jones', 'admin' => '1', 'dateAdded' => time(), 'lastLogin' => time()]],
            '42   k.jones',
            [['id' => 42, 'username' => 'k.jones']],
        ];

        yield 'Ignores non-existing columns' => [
            ['--column' => ['id', 'username', 'foobar']],
            [['id' => 42, 'username' => 'k.jones', 'name' => 'Kevin Jones', 'admin' => '1', 'dateAdded' => time(), 'lastLogin' => time()]],
            '42   k.jones',
            [['id' => 42, 'username' => 'k.jones']],
        ];

        $tstamp = time();
        $formatted = date('Y-m-d H:i:s', $tstamp);

        yield 'Formats timestamp fields' => [
            ['--column' => ['tstamp', 'dateAdded', 'lastLogin', 'foobar']],
            [['tstamp' => $tstamp, 'dateAdded' => $tstamp, 'lastLogin' => $tstamp, 'foobar' => $tstamp]],
            "$formatted   $formatted   $formatted   $tstamp",
            [['tstamp' => $tstamp, 'dateAdded' => $tstamp, 'lastLogin' => $tstamp, 'foobar' => $tstamp]],
        ];

        $check = '\\' === \DIRECTORY_SEPARATOR ? '1' : "\xE2\x9C\x94";

        yield 'Formats checkbox fields on Unix' => [
            [],
            [['id' => 42, 'username' => 'k.jones', 'name' => 'Kevin Jones', 'admin' => '1', 'dateAdded' => 1697459597, 'lastLogin' => 1697459597]],
            'k.jones    Kevin Jones   '.$check,
            [['username' => 'k.jones', 'name' => 'Kevin Jones', 'admin' => '1', 'dateAdded' => 1697459597, 'lastLogin' => 1697459597]],
        ];
    }

    public function testAdminArgumentAffectsQueryBuilder(): void
    {
        $queryBuilder = $this->mockQueryBuilder([]);
        $queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with("admin='1'")
            ->willReturnSelf()
        ;

        $command = $this->getCommand($queryBuilder);

        $this->executeCommand($command, ['--admins' => true]);
    }

    private function getCommand(QueryBuilder $queryBuilder): UserListCommand
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder)
        ;

        $command = new UserListCommand($connection);
        $command->setApplication(new Application());

        return $command;
    }

    /**
     * @return QueryBuilder&MockObject
     */
    private function mockQueryBuilder(array $result): QueryBuilder
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects($this->once())
            ->method('select')
            ->with('*')
            ->willReturnSelf()
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('from')
            ->with('tl_user')
            ->willReturnSelf()
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn($result)
        ;

        return $queryBuilder;
    }

    private function executeCommand(UserListCommand $command, array $input = [], int $expectedExitCode = 0): string
    {
        $commandTester = new CommandTester($command);
        $code = $commandTester->execute($input);

        $this->assertSame($expectedExitCode, $code);

        return $commandTester->getDisplay();
    }
}
