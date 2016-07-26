<?php

namespace Contao\CoreBundle\EventListener;

use Doctrine\DBAL\Event\SchemaAlterTableEventArgs;
use Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;

class DoctrineSchemaListener
{

    public function onSchemaIndexDefinition(SchemaIndexDefinitionEventArgs $event)
    {
        $connection = $event->getConnection();
        $data = $event->getTableIndex();

        if (!$connection->getDatabasePlatform() instanceof MySqlPlatform || 'PRIMARY' === $data['name']) {
            return;
        }

        $index = $connection->fetchAssoc(
            sprintf("SHOW INDEX FROM %s WHERE Key_name='%s'", $event->getTable(), $data['name'])
        );

        if (null !== $index['Sub_part']) {
            $columns = [];
            foreach ($data['columns'] as $col) {
                $columns[$col] = sprintf('%s(%s)', $col, $index['Sub_part']);
            }

            $event->setIndex(new Index($data['name'], $columns, $data['unique'], $data['primary'], $data['flags'], $data['options']));
            $event->preventDefault();
        }
    }
}
