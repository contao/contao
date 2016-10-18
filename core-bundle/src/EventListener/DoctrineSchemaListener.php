<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Index;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class DoctrineSchemaListener
{
    /**
     * @var DcaSchemaProvider
     */
    private $provider;

    /**
     * Constructor.
     *
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
    public function postGenerateSchema(GenerateSchemaEventArgs $event)
    {
        $this->provider->appendToSchema($event->getSchema());
    }

    /**
     * Handles the Doctrine schema and overrides the indexes with a fixed length.
     *
     * @param SchemaIndexDefinitionEventArgs $event
     */
    public function onSchemaIndexDefinition(SchemaIndexDefinitionEventArgs $event)
    {
        $connection = $event->getConnection();
        $data = $event->getTableIndex();

        if (!($connection->getDatabasePlatform() instanceof MySqlPlatform) || 'PRIMARY' === $data['name']) {
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
