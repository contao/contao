<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\Database;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DatabaseTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([Database::class, System::class]);

        parent::tearDown();
    }

    public function testTableAndDatabaseCacheDoNotCollide(): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager
            ->method('listTableNames')
            ->willReturn(['samename'])
        ;

        $result = $this->createMock(Result::class);
        $result
            ->method('columnCount')
            ->willReturn(1)
        ;

        $result
            ->method('fetchAssociative')
            ->willReturnOnConsecutiveCalls(
                ['Field' => 'columnname', 'Type' => 'varchar(255)'],
                false,
                false,
                ['Field' => 'columnname', 'Type' => 'varchar(255)'],
                false,
                false
            )
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('getDatabase')
            ->willReturn('samename')
        ;

        $connection
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->method('executeQuery')
            ->willReturnMap([
                ['SHOW FULL COLUMNS FROM samename', [], [], null, $result],
                ['SHOW INDEXES FROM `samename`', [], [], null, $result],
            ])
        ;

        $container = new ContainerBuilder();
        $container->set('database_connection', $connection);

        System::setContainer($container);

        $database = Database::getInstance();

        // Without cache
        $this->assertSame(['samename'], $database->listTables('samename', true));
        $this->assertSame('columnname', $database->listFields('samename', true)[0]['name']);

        // With cache
        $this->assertSame(['samename'], $database->listTables('samename'));
        $this->assertSame(['samename'], $database->listTables('samename'));
        $this->assertSame('columnname', $database->listFields('samename')[0]['name']);
        $this->assertSame('columnname', $database->listFields('samename')[0]['name']);
    }
}
