<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\EventListener;

use Contao\ManagerBundle\EventListener\DoctrineAlterTableListener;
use Doctrine\DBAL\Event\SchemaAlterTableRenameColumnEventArgs;
use Doctrine\DBAL\Platforms\MySQL57Platform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

class DoctrineAlterTableListenerTest extends TestCase
{
    public function testConvertsRenameToDropAndAdd()
    {
        $table = new Table('tl_member');
        $table->addColumn('bar', Type::INTEGER);

        $column = new Column('foo', Type::getType(Type::INTEGER));

        $tableDiff = new TableDiff('tl_member');
        $tableDiff->renamedColumns['bar'] = $column;
        $tableDiff->fromTable = $table;

        $args = new SchemaAlterTableRenameColumnEventArgs('bar', $column, $tableDiff, new MySQL57Platform());

        $this->assertEmpty($args->getSql());

        $listener = new DoctrineAlterTableListener();
        $listener->onSchemaAlterTableRenameColumn($args);

        $this->assertSame(['ALTER TABLE tl_member ADD foo INT NOT NULL, DROP bar'], $args->getSql());
    }
}
