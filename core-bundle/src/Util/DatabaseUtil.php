<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Contao\CoreBundle\Util;

use Doctrine\DBAL\Connection;

class DatabaseUtil
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function quoteIdentifier(string $identifier): string
    {
        // Quoted already or not an identifier
        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $identifier)) {
            return $identifier;
        }

        // Backwards-compatibility for doctrine/dbal < 4.3
        if (!method_exists(Connection::class, 'quoteSingleIdentifier')) {
            return $this->connection->quoteIdentifier($identifier);
        }

        // Restore functionality of Connection::quoteIdentifier()
        if (str_contains($identifier, '.')) {
            return implode('.', array_map($this->connection->quoteSingleIdentifier(...), explode('.', $identifier)));
        }

        return $this->connection->quoteSingleIdentifier($identifier);
    }
}
