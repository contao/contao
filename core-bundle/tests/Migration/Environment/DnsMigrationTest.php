<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Migration\Environment;

use Contao\CoreBundle\Migration\Environment\DnsMigration;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

class DnsMigrationTest extends TestCase
{
    public function testDoesNotRunIfNoMappings(): void
    {
        $db = $this->createMock(Connection::class);

        $migration = new DnsMigration($db, []);

        $this->assertFalse($migration->shouldRun());
    }

    public function testDoesNotRunIfTableDoesNotExist(): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_page'])
            ->willReturn(false)
        ;

        $db = $this->createMock(Connection::class);
        $db
            ->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $migration = new DnsMigration($db, ['foobar.com' => 'foobar.local']);

        $this->assertFalse($migration->shouldRun());
    }

    public function testDoesNotRunIfFieldsDoNotExist(): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_page'])
            ->willReturn(true)
        ;

        $schemaManager
            ->expects($this->once())
            ->method('listTableColumns')
            ->with('tl_page')
            ->willReturn([])
        ;

        $db = $this->createMock(Connection::class);
        $db
            ->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $migration = new DnsMigration($db, ['foobar.com' => 'foobar.local']);

        $this->assertFalse($migration->shouldRun());
    }

    /**
     * @dataProvider getShouldRunMappings
     */
    public function testShouldRun(array $mapping, string $query, array $params): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_page'])
            ->willReturn(true)
        ;

        $schemaManager
            ->expects($this->once())
            ->method('listTableColumns')
            ->with('tl_page')
            ->willReturn(['dns' => true, 'type' => true, 'usessl' => true])
        ;

        $db = $this->createMock(Connection::class);
        $db
            ->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $queryBuilder = new QueryBuilder($db);

        $db
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder)
        ;

        $exprBuilder = new ExpressionBuilder($db);

        $db
            ->expects($this->once())
            ->method('getExpressionBuilder')
            ->willReturn($exprBuilder)
        ;

        $db
            ->expects($this->once())
            ->method('executeQuery')
            ->with($query, $params)
        ;

        $migration = new DnsMigration($db, $mapping);
        $migration->shouldRun();
    }

    public function getShouldRunMappings(): \Generator
    {
        yield [
            ['example.com' => 'example.local'],
            "SELECT TRUE FROM tl_page WHERE (type = 'root') AND (dns = :fromHost) AND (dns != :toHost)",
            ['fromHost' => 'example.com', 'toHost' => 'example.local'],
        ];

        yield [
            ['//example.com' => 'example.local'],
            "SELECT TRUE FROM tl_page WHERE (type = 'root') AND (dns = :fromHost) AND (dns != :toHost)",
            ['fromHost' => 'example.com', 'toHost' => 'example.local'],
        ];

        yield [
            ['example.com' => '//example.local'],
            "SELECT TRUE FROM tl_page WHERE (type = 'root') AND (dns = :fromHost) AND (dns != :toHost)",
            ['fromHost' => 'example.com', 'toHost' => 'example.local'],
        ];

        yield [
            ['example.com' => 'http://example.local'],
            "SELECT TRUE FROM tl_page WHERE (type = 'root') AND (dns = :fromHost) AND ((useSSL = 1) OR (dns != :toHost))",
            ['fromHost' => 'example.com', 'toHost' => 'example.local'],
        ];

        yield [
            ['example.com' => 'https://example.local'],
            "SELECT TRUE FROM tl_page WHERE (type = 'root') AND (dns = :fromHost) AND ((useSSL != 1) OR (dns != :toHost))",
            ['fromHost' => 'example.com', 'toHost' => 'example.local'],
        ];

        yield [
            ['https://example.com' => 'http://example.com'],
            "SELECT TRUE FROM tl_page WHERE (type = 'root') AND (useSSL = 1) AND (dns = :fromHost) AND ((useSSL = 1) OR (dns != :toHost))",
            ['fromHost' => 'example.com', 'toHost' => 'example.com'],
        ];

        yield [
            ['example.com' => ''],
            "SELECT TRUE FROM tl_page WHERE (type = 'root') AND (dns = :fromHost) AND (dns != :toHost)",
            ['fromHost' => 'example.com', 'toHost' => ''],
        ];

        yield [
            ['example.com' => '//'],
            "SELECT TRUE FROM tl_page WHERE (type = 'root') AND (dns = :fromHost) AND (dns != :toHost)",
            ['fromHost' => 'example.com', 'toHost' => ''],
        ];

        yield [
            ['' => 'example.local'],
            "SELECT TRUE FROM tl_page WHERE (type = 'root') AND (dns = :fromHost) AND (dns != :toHost)",
            ['fromHost' => '', 'toHost' => 'example.local'],
        ];

        yield [
            ['//' => 'example.local'],
            "SELECT TRUE FROM tl_page WHERE (type = 'root') AND (dns = :fromHost) AND (dns != :toHost)",
            ['fromHost' => '', 'toHost' => 'example.local'],
        ];
    }

    /**
     * @dataProvider getRunMappings
     */
    public function testRun(array $mapping, string $query, array $params): void
    {
        $db = $this->createMock(Connection::class);

        $queryBuilder = new QueryBuilder($db);

        $db
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder)
        ;

        $db
            ->expects($this->once())
            ->method('executeQuery')
            ->with($query, $params)
        ;

        $migration = new DnsMigration($db, $mapping);
        $migration->run();
    }

    public function getRunMappings(): \Generator
    {
        yield [
            ['example.com' => 'example.local'],
            "UPDATE tl_page SET dns = :toHost WHERE (type = 'root') AND (dns = :fromHost)",
            ['fromHost' => 'example.com', 'toHost' => 'example.local'],
        ];

        yield [
            ['//example.com' => 'example.local'],
            "UPDATE tl_page SET dns = :toHost WHERE (type = 'root') AND (dns = :fromHost)",
            ['fromHost' => 'example.com', 'toHost' => 'example.local'],
        ];

        yield [
            ['example.com' => '//example.local'],
            "UPDATE tl_page SET dns = :toHost WHERE (type = 'root') AND (dns = :fromHost)",
            ['fromHost' => 'example.com', 'toHost' => 'example.local'],
        ];

        yield [
            ['example.com' => 'http://example.local'],
            "UPDATE tl_page SET useSSL = :useSSL, dns = :toHost WHERE (type = 'root') AND (dns = :fromHost)",
            ['fromHost' => 'example.com', 'toHost' => 'example.local', 'useSSL' => false],
        ];

        yield [
            ['example.com' => 'https://example.local'],
            "UPDATE tl_page SET useSSL = :useSSL, dns = :toHost WHERE (type = 'root') AND (dns = :fromHost)",
            ['fromHost' => 'example.com', 'toHost' => 'example.local', 'useSSL' => true],
        ];

        yield [
            ['https://example.com' => 'http://example.com'],
            "UPDATE tl_page SET useSSL = :useSSL, dns = :toHost WHERE (type = 'root') AND (useSSL = 1) AND (dns = :fromHost)",
            ['fromHost' => 'example.com', 'toHost' => 'example.com', 'useSSL' => false],
        ];

        yield [
            ['example.com' => ''],
            "UPDATE tl_page SET dns = :toHost WHERE (type = 'root') AND (dns = :fromHost)",
            ['fromHost' => 'example.com', 'toHost' => ''],
        ];

        yield [
            ['example.com' => '//'],
            "UPDATE tl_page SET dns = :toHost WHERE (type = 'root') AND (dns = :fromHost)",
            ['fromHost' => 'example.com', 'toHost' => ''],
        ];

        yield [
            ['' => 'example.local'],
            "UPDATE tl_page SET dns = :toHost WHERE (type = 'root') AND (dns = :fromHost)",
            ['fromHost' => '', 'toHost' => 'example.local'],
        ];

        yield [
            ['//' => 'example.local'],
            "UPDATE tl_page SET dns = :toHost WHERE (type = 'root') AND (dns = :fromHost)",
            ['fromHost' => '', 'toHost' => 'example.local'],
        ];
    }
}
