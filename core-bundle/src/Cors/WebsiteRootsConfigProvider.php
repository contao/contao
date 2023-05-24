<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Cors;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Nelmio\CorsBundle\Options\ProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class WebsiteRootsConfigProvider implements ProviderInterface
{
    private Connection $connection;

    /**
     * @internal
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getOptions(Request $request): array
    {
        if (!$this->isCorsRequest($request) || !$this->canRunDbQuery()) {
            return [];
        }

        $stmt = $this->connection->prepare("
            SELECT EXISTS (
                SELECT
                    id
                FROM
                    tl_page
                WHERE
                    type = 'root' AND dns = :dns
            )
        ");

        $stmt->bindValue('dns', preg_replace('@^https?://@', '', $request->headers->get('origin')));

        if (!$stmt->executeQuery()->fetchOne()) {
            return [];
        }

        return [
            'allow_origin' => true,
            'allow_methods' => ['HEAD', 'GET'],
            'allow_headers' => ['x-requested-with'],
        ];
    }

    /**
     * Checks if the request has an Origin header.
     */
    private function isCorsRequest(Request $request): bool
    {
        return $request->headers->has('Origin')
            && $request->headers->get('Origin') !== $request->getSchemeAndHttpHost();
    }

    /**
     * Checks if the tl_page table exists.
     */
    private function canRunDbQuery(): bool
    {
        try {
            return $this->connection->createSchemaManager()->tablesExist(['tl_page']);
        } catch (DriverException $e) {
            return false;
        }
    }
}
