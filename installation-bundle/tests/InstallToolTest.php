<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\Tests;

use Contao\CoreBundle\Migration\MigrationCollection;
use Contao\InstallationBundle\InstallTool;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Doctrine\DBAL\Driver\Mysqli\Driver as MysqliDriver;
use Doctrine\DBAL\Driver\PDO\MySQL\Driver as PdoDriver;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class InstallToolTest extends TestCase
{
    /**
     * @dataProvider provideInvalidSqlModes
     */
    public function testRaisesErrorIfNonRunningInStrictMode(string $sqlMode, AbstractMySQLDriver $driver, int $expectedOptionKey): void
    {
        $context = [];

        $installTool = $this->getInstallTool($sqlMode, $driver);
        $installTool->checkStrictMode($context);

        $this->assertSame($expectedOptionKey, $context['optionKey']);
    }

    public function provideInvalidSqlModes(): \Generator
    {
        $pdoDriver = new PdoDriver();
        $mysqliDriver = new MysqliDriver();

        yield 'empty sql_mode, pdo driver' => [
            '', $pdoDriver, 1002,
        ];

        yield 'empty sql_mode, mysqli driver' => [
            '', $mysqliDriver, 3,
        ];

        yield 'unrelated values, pdo driver' => [
            'IGNORE_SPACE,ONLY_FULL_GROUP_BY', $pdoDriver, 1002,
        ];

        yield 'unrelated values, mysqli driver' => [
            'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION', $mysqliDriver, 3,
        ];
    }

    /**
     * @dataProvider provideValidSqlModes
     */
    public function testRunsInStrictMode(string $sqlMode): void
    {
        $context = [];

        $installTool = $this->getInstallTool($sqlMode);
        $installTool->checkStrictMode($context);

        $this->assertEmpty($context);
    }

    public function provideValidSqlModes(): \Generator
    {
        yield 'TRADITIONAL' => [
            'TRADITIONAL',
        ];

        yield 'STRICT_ALL_TABLES' => [
            'STRICT_ALL_TABLES',
        ];

        yield 'STRICT_TRANS_TABLES' => [
            'STRICT_TRANS_TABLES',
        ];

        yield 'mixed' => [
            'IGNORE_SPACE,ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION',
        ];
    }

    private function getInstallTool(string $sqlMode, ?AbstractMySQLDriver $driver = null): InstallTool
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchOne')
            ->willReturnMap([
                ['SELECT @@version', [], [], '8.0.0-system'],
                ['SELECT @@sql_mode', [], [], $sqlMode],
            ])
        ;

        $connection
            ->method('getParams')
            ->willReturn([
                'defaultTableOptions' => [],
            ])
        ;

        $connection
            ->method('getDriver')
            ->willReturn($driver ?? new PdoDriver())
        ;

        return new InstallTool($connection, '/project/dir', new NullLogger(), $this->createMock(MigrationCollection::class));
    }
}
