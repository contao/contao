<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer\Undo;

use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
trait UndoListenerTrait
{
    private Connection $connection;
    private ContaoFramework $framework;

    private function getParentTableForRow(string $table, array $row): ?array
    {
        if (isset($GLOBALS['TL_DCA'][$table]['config']['dynamicPtable']) && true === $GLOBALS['TL_DCA'][$table]['config']['dynamicPtable']) {
            return ['table' => $row['ptable'], 'id' => $row['pid']];
        }

        if (isset($GLOBALS['TL_DCA'][$table]['config']['ptable'])) {
            return ['table' => $GLOBALS['TL_DCA'][$table]['config']['ptable'], 'id' => $row['pid']];
        }

        return null;
    }

    private function getTranslatedTypeFromTable(string $table): string
    {
        return isset($GLOBALS['TL_LANG']['TABLES'][$table]) ? $GLOBALS['TL_LANG']['TABLES'][$table][0] : $table;
    }

    private function checkIfParentExists(array $parent): bool
    {
        $count = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM {$this->connection->quoteIdentifier($parent['table'])} WHERE id = :id",
            [
                'id' => $parent['id'],
            ]
        );

        return (int) $count > 0;
    }
}
