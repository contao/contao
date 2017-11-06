<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Index;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

class DoctrineSchemaListener
{
    /**
     * @var DcaSchemaProvider
     */
    private $provider;

    /**
     * @param DcaSchemaProvider $provider
     */
    public function __construct(DcaSchemaProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Adds the Contao DCA information to the Doctrine schema.
     *
     * @param GenerateSchemaEventArgs $event
     */
    public function postGenerateSchema(GenerateSchemaEventArgs $event): void
    {
        $this->provider->appendToSchema($event->getSchema());
    }

    /**
     * Handles the Doctrine schema and overrides the indexes with a fixed length.
     *
     * @param SchemaIndexDefinitionEventArgs $event
     */
    public function onSchemaIndexDefinition(SchemaIndexDefinitionEventArgs $event): void
    {
        $connection = $event->getConnection();
        $data = $event->getTableIndex();

        if ('PRIMARY' === $data['name'] || !$connection->getDatabasePlatform() instanceof MySqlPlatform) {
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

            $event->setIndex(
                new Index(
                    $data['name'],
                    $columns,
                    $data['unique'],
                    $data['primary'],
                    $data['flags'],
                    $data['options'])
            );

            $event->preventDefault();
        }
    }
}
