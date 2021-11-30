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

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
trait UndoListenerTrait
{
    private Connection $connection;
    private ContaoFramework $framework;

    private function getParentTableForRow(string $table, array $data): ?array
    {
        if (isset($GLOBALS['TL_DCA'][$table]['config']['dynamicPtable']) && true === $GLOBALS['TL_DCA'][$table]['config']['dynamicPtable']) {
            return ['table' => $data['ptable'], 'id' => $data['pid']];
        }

        if (isset($GLOBALS['TL_DCA'][$table]['config']['ptable'])) {
            return ['table' => $GLOBALS['TL_DCA'][$table]['config']['ptable'], 'id' => $data['pid']];
        }

        return null;
    }

    private function getTranslatedTypeFromTable(string $table): string
    {
        /** @var Controller $controller */
        $controller = $this->framework->getAdapter(Controller::class);
        $controller->loadLanguageFile($table);

        return isset($GLOBALS['TL_LANG'][$table]['_type']) ? $GLOBALS['TL_LANG'][$table]['_type'][0] : $table;
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
