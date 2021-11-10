<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DoctrineDbalException;
use Symfony\Bridge\Doctrine\CacheWarmer\ProxyCacheWarmer;

class FailTolerantProxyCacheWarmer extends ProxyCacheWarmer
{
    private ProxyCacheWarmer $inner;
    private Connection $connection;

    public function __construct(ProxyCacheWarmer $inner, Connection $connection)
    {
        $this->inner = $inner;
        $this->connection = $connection;
    }

    public function isOptional(): bool
    {
        return (bool) $this->inner->isOptional();
    }

    /**
     * @return array<string>
     */
    public function warmUp(string $cacheDir): array
    {
        // If there are no DB credentials yet (install tool) and the
        // server_version was not configured, we have to skip the ORM warmup to
        // prevent a DBAL exception during the automatic version detection
        try {
            $this->connection->getDatabasePlatform();
        } catch (DoctrineDbalException | \mysqli_sql_exception $e) {
            return [];
        }

        $this->inner->warmUp($cacheDir);

        return [];
    }
}
