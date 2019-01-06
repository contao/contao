<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\TestCase;

trait ContaoDatabaseTrait
{
    private static $pdo;

    protected static function loadFileIntoDatabase(string $sqlFile): void
    {
        if (!file_exists($sqlFile)) {
            throw new \InvalidArgumentException(sprintf('File "%s" does not exist', $sqlFile));
        }

        $pdo = static::getConnection();
        $pdo->exec(file_get_contents($sqlFile));
    }

    protected static function getConnection(): \PDO
    {
        if (null === self::$pdo) {
            self::$pdo = new \PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;', getenv('DB_HOST'), getenv('DB_PORT'), getenv('DB_NAME')),
                getenv('DB_USER'),
                getenv('DB_PASS'),
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        }

        return self::$pdo;
    }
}
