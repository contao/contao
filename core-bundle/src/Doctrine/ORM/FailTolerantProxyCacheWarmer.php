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
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class FailTolerantProxyCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private CacheWarmerInterface $inner,
        private Connection $connection,
    ) {
    }

    public function isOptional(): bool
    {
        return $this->inner->isOptional();
    }

    /**
     * @return array<string>
     */
    public function warmUp(string $cacheDir): array
    {
        // If there are no DB credentials yet and the server_version was not
        // configured, we have to skip the ORM warmup to prevent a DBAL
        // exception during the automatic version detection
        try {
            $this->connection->getDatabasePlatform();
        } catch (DoctrineDbalException|\mysqli_sql_exception) {
            return [];
        }

        $this->inner->warmUp($cacheDir);

        return [];
    }
}
