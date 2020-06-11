<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\EventListener;

use Doctrine\DBAL\Event\SchemaAlterTableRenameColumnEventArgs;

/**
 * @internal
 */
class DoctrineListener
{
    /**
     * Prevents renaming arbitrary columns by explicitly dropping the old ones
     * and adding the new ones (see #1716).
     */
    public function onSchemaAlterTableRenameColumn(SchemaAlterTableRenameColumnEventArgs $args): void
    {
        $args->preventDefault();

        $platform = $args->getPlatform();
        $table = $args->getTableDiff()->getName($platform)->getQuotedName($platform);
        $column = $args->getColumn();

        $args->addSql(sprintf(
            'ALTER TABLE %s DROP %s',
            $table,
            $platform->quoteIdentifier($args->getOldColumnName())
        ));

        $args->addSql(sprintf(
            'ALTER TABLE %s ADD %s',
            $table,
            $platform->getColumnDeclarationSQL($column->getQuotedName($platform), $column->toArray())
        ));
    }
}
