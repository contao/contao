<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Index;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

class DoctrineSchemaListener
{
    /**
     * @var DcaSchemaProvider
     */
    private $provider;

    public function __construct(DcaSchemaProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Adds the Contao DCA information to the Doctrine schema.
     */
    public function postGenerateSchema(GenerateSchemaEventArgs $event): void
    {
        $this->provider->appendToSchema($event->getSchema());
    }

    /**
     * Adds the index length on MySQL platforms for Doctrine DBAL <2.9.
     */
    public function onSchemaIndexDefinition(SchemaIndexDefinitionEventArgs $event): void
    {
        // Skip for doctrine/dbal >= 2.9
        if (method_exists(AbstractPlatform::class, 'supportsColumnLengthIndexes')) {
            return;
        }

        $connection = $event->getConnection();

        if (!$connection->getDatabasePlatform() instanceof MySqlPlatform) {
            return;
        }

        $data = $event->getTableIndex();

        // Ignore primary keys
        if ('PRIMARY' === $data['name']) {
            return;
        }

        $columns = [];
        $query = sprintf("SHOW INDEX FROM %s WHERE Key_name='%s'", $event->getTable(), $data['name']);
        $result = $connection->executeQuery($query);

        while ($row = $result->fetch()) {
            if (null !== $row['Sub_part']) {
                $columns[] = sprintf('%s(%s)', $row['Column_name'], $row['Sub_part']);
            } else {
                $columns[] = $row['Column_name'];
            }
        }

        $event->setIndex(
            new Index(
                $data['name'],
                $columns,
                $data['unique'],
                $data['primary'],
                $data['flags'],
                $data['options']
            )
        );

        $event->preventDefault();
    }
}
