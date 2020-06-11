<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\EventListener;

use Contao\ManagerBundle\EventListener\DoctrineListener;
use Doctrine\DBAL\Event\SchemaAlterTableRenameColumnEventArgs;
use Doctrine\DBAL\Platforms\MySQL57Platform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

class DoctrineListenerTest extends TestCase
{
    public function testConvertsRenameToDropAndAdd()
    {
        $column = new Column('bar', Type::getType(Type::INTEGER));

        $tableDiff = new TableDiff('tl_member');
        $tableDiff->renamedColumns['foo'] = new Column('foo', Type::getType(Type::INTEGER));

        $args = new SchemaAlterTableRenameColumnEventArgs('foo', $column, $tableDiff, new MySQL57Platform());

        $this->assertEmpty($args->getSql());

        $listener = new DoctrineListener();
        $listener->onSchemaAlterTableRenameColumn($args);

        $this->assertSame(
            [
                'ALTER TABLE tl_member DROP `foo`',
                'ALTER TABLE tl_member ADD bar INT NOT NULL',
            ],
            $args->getSql()
        );
    }
}
