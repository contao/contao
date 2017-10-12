<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Cors;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Nelmio\CorsBundle\Options\ProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class WebsiteRootsConfigProvider implements ProviderInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
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
        $stmt->execute();

        if (!$stmt->fetchColumn()) {
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
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isCorsRequest(Request $request): bool
    {
        return $request->headers->has('Origin')
            && $request->headers->get('Origin') !== $request->getSchemeAndHttpHost()
        ;
    }

    /**
     * Checks if a database connection can be established and the table exist.
     *
     * @return bool
     */
    private function canRunDbQuery(): bool
    {
        try {
            return $this->connection->isConnected() && $this->connection->getSchemaManager()->tablesExist(['tl_page']);
        } catch (DriverException $e) {
            return false;
        }
    }
}
