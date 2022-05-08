<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DoctrineSchema;

use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

/**
 * Removes the tl_search, tl_search_index and tl_search_term tables from the schema,
 * if the default indexer is disabled.
 *
 * @internal
 */
class RemoveSearchTablesListener
{
    private static $tables = ['tl_search', 'tl_search_index', 'tl_search_term'];

    public function __construct(private bool $defaultIndexerEnabled)
    {
    }

    public function __invoke(GenerateSchemaEventArgs $event): void
    {
        if ($this->defaultIndexerEnabled) {
            return;
        }

        $schema = $event->getSchema();

        foreach (self::$tables as $table) {
            if ($schema->hasTable($table)) {
                $schema->dropTable($table);
            }
        }
    }
}
