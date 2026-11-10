<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\Schema;

use Contao\CoreBundle\Crawl\Escargot\Factory;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * @internal
 */
class EscargotSchemaProvider
{
    public function __construct(private readonly Factory $escargotFactory)
    {
    }

    /**
     * Adds the Escargot crawl queue to the Doctrine schema.
     */
    public function appendToSchema(Schema $schema): void
    {
        $queueTable = $this->escargotFactory->createDoctrineQueue()->getTableSchema();
        $table = $schema->createTable($queueTable->getName());

        foreach ($queueTable->getColumns() as $column) {
            $options = $column->toArray();
            unset($options['name'], $options['type'], $options['typeName']);

            $table->addColumn($column->getName(), Type::lookupName($column->getType()), $options);
        }

        foreach ($queueTable->getIndexes() as $index) {
            if ($index->isPrimary()) {
                $table->setPrimaryKey($index->getColumns(), $index->getName());
            } elseif ($index->isUnique()) {
                $table->addUniqueIndex($index->getColumns(), $index->getName(), $index->getOptions());
            } else {
                $table->addIndex($index->getColumns(), $index->getName(), $index->getFlags(), $index->getOptions());
            }
        }

        foreach ($queueTable->getOptions() as $name => $value) {
            $table->addOption($name, $value);
        }
    }
}
