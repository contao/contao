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

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Doctrine\DBAL\Connection;

/**
 * @Callback(table="tl_undo", target="fields.options.fromTable")
 *
 * @internal
 */
class FromTableOptionsListener
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function __invoke(): array
    {
        $tables = $this->connection->executeQuery('SELECT DISTINCT '.$this->connection->quoteIdentifier('fromTable').' FROM tl_undo');

        if (0 === $tables->rowCount()) {
            return [];
        }

        $options = [];

        foreach ($tables->fetchFirstColumn() as $table) {
            $options[] = $table;
        }

        return $options;
    }
}
