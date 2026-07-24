<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Migration;

use Contao\CoreBundle\Doctrine\Schema\MysqlInnodbRowSizeCalculator;
use Contao\CoreBundle\Migration\CommandCompiler;
use Contao\CoreBundle\Migration\DatabaseMigrationChecks;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Doctrine\DBAL\Driver\Mysqli\Driver as MysqliDriver;
use Doctrine\DBAL\Driver\PDO\MySQL\Driver as PdoDriver;
use Doctrine\DBAL\Schema\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DatabaseMigrationChecksTest extends TestCase
{
    public function testReportsUnsupportedDatabaseVersion(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection
            ->method('fetchOne')
            ->with('SELECT @@version')
            ->willReturn('5.0.10')
        ;

        $checks = $this->createChecks($connection);
        $errors = $checks->compileConfigurationErrors();

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Your database version is not supported!', $errors[0]);
    }

    public function testCompilesSchemaWarningsWithoutDropStatements(): void
    {
        $schema = new Schema();
        $table = $schema->createTable('tl_foo');
        $table->addOption('engine', 'InnoDB');

        $compiler = $this->createMock(CommandCompiler::class);
        $compiler
            ->expects($this->once())
            ->method('compileTargetSchema')
            ->with(true)
            ->willReturn($schema)
        ;

        $calculator = $this->createStub(MysqlInnodbRowSizeCalculator::class);
        $calculator
            ->method('getMysqlRowSize')
            ->willReturn(1)
        ;

        $calculator
            ->method('getMysqlRowSizeLimit')
            ->willReturn(2)
        ;

        $calculator
            ->method('getInnodbRowSize')
            ->willReturn(1)
        ;

        $calculator
            ->method('getInnodbRowSizeLimit')
            ->willReturn(2)
        ;

        $checks = new DatabaseMigrationChecks($this->createStub(Connection::class), $compiler, $calculator);

        $this->assertSame([], $checks->compileSchemaWarnings(true));
    }

    #[DataProvider('provideInvalidSqlModes')]
    public function testCompilesWarningsForNonStrictSqlModes(string $sqlMode, AbstractMySQLDriver $driver, int $expectedOptionKey): void
    {
        $connection = $this->createStub(Connection::class);
        $connection
            ->method('fetchOne')
            ->with('SELECT @@sql_mode')
            ->willReturn($sqlMode)
        ;

        $connection
            ->method('getDriver')
            ->willReturn($driver)
        ;

        $warnings = new DatabaseMigrationChecks(
            $connection,
            $this->createStub(CommandCompiler::class),
            $this->createStub(MysqlInnodbRowSizeCalculator::class),
        )->compileConfigurationWarnings();

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('Running MySQL in non-strict mode can cause corrupt or truncated data.', $warnings[0]);
        $this->assertStringContainsString(\sprintf('%s: "SET SESSION sql_mode=', $expectedOptionKey), $warnings[0]);
    }

    public static function provideInvalidSqlModes(): iterable
    {
        yield 'empty sql_mode, pdo driver' => ['', new PdoDriver(), 1002];
        yield 'empty sql_mode, mysqli driver' => ['', new MysqliDriver(), 3];
        yield 'unrelated values, pdo driver' => ['IGNORE_SPACE,ONLY_FULL_GROUP_BY', new PdoDriver(), 1002];
        yield 'unrelated values, mysqli driver' => ['NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION', new MysqliDriver(), 3];
    }

    private function createChecks(Connection $connection): DatabaseMigrationChecks
    {
        return new DatabaseMigrationChecks(
            $connection,
            $this->createStub(CommandCompiler::class),
            $this->createStub(MysqlInnodbRowSizeCalculator::class),
        );
    }
}
