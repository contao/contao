<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Util\Database;

class ForeignKeyExpression
{
    private string|null $columnName = null;

    private string|null $key = null;

    public function __construct(
        private string $tableName,
        private string $columnExpression,
    ) {
    }

    /**
     * The table name, always safe to use in queries.
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * The column expression, can contain an arbitrary expression.
     */
    public function getColumnExpression(): string
    {
        return $this->columnExpression;
    }

    /**
     * An optional key.
     */
    public function getKey(): string|null
    {
        return $this->key;
    }

    /**
     * The column name, always safe to use in queries if available.
     */
    public function getColumnName(): string|null
    {
        return $this->columnName;
    }

    public function withColumnName(string|null $columnName): self
    {
        $clone = clone $this;
        $clone->columnName = $columnName;

        return $clone;
    }

    public function withKey(string|null $key): self
    {
        $clone = clone $this;
        $clone->key = $key;

        return $clone;
    }
}
