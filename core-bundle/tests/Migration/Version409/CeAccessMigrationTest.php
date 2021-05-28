<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Migration\Version409;

use Contao\ContentText;
use Contao\CoreBundle\Migration\Version409\CeAccessMigration;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FormTextField;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\MySqlSchemaManager;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type;

class CeAccessMigrationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $GLOBALS['TL_CTE'] = [
            'texts' => [
                'text' => ContentText::class,
            ],
        ];

        $GLOBALS['TL_FFL'] = [
            'text' => FormTextField::class,
        ];
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        unset($GLOBALS['TL_CTE'], $GLOBALS['TL_FFL']);
    }

    public function testActivatesTheFieldsInAllUserGroups(): void
    {
        $schemaManager = $this->createMock(MySqlSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_user_group'])
            ->willReturn(true)
        ;

        $schemaManager
            ->expects($this->once())
            ->method('listTableColumns')
            ->willReturn([])
        ;

        $stmt = $this->createMock(Statement::class);
        $stmt
            ->expects($this->once())
            ->method('executeStatement')
            ->with([':elements' => 'a:1:{i:0;s:4:"text";}', ':fields' => 'a:1:{i:0;s:4:"text";}'])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('getSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->expects($this->once())
            ->method('executeStatement')
        ;

        $connection
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt)
        ;

        $migration = new CeAccessMigration($connection, $this->mockContaoFramework());

        $this->assertTrue($migration->shouldRun());
        $this->assertTrue($migration->run()->isSuccessful());
    }

    public function testDoesNothingIfTheUserGroupTableDoesNotExist(): void
    {
        $schemaManager = $this->createMock(MySqlSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_user_group'])
            ->willReturn(false)
        ;

        $schemaManager
            ->expects($this->never())
            ->method('listTableColumns')
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('getSchemaManager')
            ->willReturn($schemaManager)
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $migration = new CeAccessMigration($connection, $framework);

        $this->assertFalse($migration->shouldRun());
    }

    public function testDoesNothingIfTheElementsColumnDoesNotExist(): void
    {
        $schemaManager = $this->createMock(MySqlSchemaManager::class);
        $schemaManager
            ->expects($this->once())
            ->method('tablesExist')
            ->with(['tl_user_group'])
            ->willReturn(true)
        ;

        $schemaManager
            ->expects($this->once())
            ->method('listTableColumns')
            ->willReturn(['elements' => new Column('elements', Type::getType('string'))])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('getSchemaManager')
            ->willReturn($schemaManager)
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $migration = new CeAccessMigration($connection, $framework);

        $this->assertFalse($migration->shouldRun());
    }
}
