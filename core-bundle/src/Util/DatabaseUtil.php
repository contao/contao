<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Util;

use Contao\CoreBundle\Util\Database\ForeignKeyExpression;
use Doctrine\DBAL\Connection;

class DatabaseUtil
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function parseForeignKeyExpression(string $foreignKeyDefinition): ForeignKeyExpression
    {
        $isIdentifier = static fn (string $expression): bool => 1 === preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $expression);

        $key = null;
        [$tableExpression, $columnExpression] = explode('.', $foreignKeyDefinition, 2);

        if (str_contains($tableExpression, ':')) {
            [$key, $tableExpression] = explode(':', $tableExpression, 2);
        }

        // Table name must be an identifier
        if (!$isIdentifier($tableExpression)) {
            throw new \InvalidArgumentException('Invalid foreign key expression: '.$foreignKeyDefinition);
        }

        // If column expression is safe, quote it for the expression (to support e.g. If
        // the expression is a single identifier, quote it to support reserved column
        // names such as "group"
        if ($isIdentifier($columnExpression)) {
            $columnName = $columnExpression; // The expression is safe here

            // Backwards-compatibility for doctrine/dbal < 4.3
            if (!method_exists(Connection::class, 'quoteSingleIdentifier')) {
                $columnExpression = $this->connection->quoteIdentifier($columnExpression);
            } else {
                $columnExpression = $this->connection->quoteSingleIdentifier($columnExpression);
            }

            $expression = new ForeignKeyExpression($tableExpression, $columnExpression);
            $expression = $expression->withColumnName($columnName);
        } else {
            $expression = new ForeignKeyExpression($tableExpression, $columnExpression);
        }

        return $expression->withKey($key ?? null);
    }
}
