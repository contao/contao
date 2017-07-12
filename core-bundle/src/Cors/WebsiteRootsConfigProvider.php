<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Cors;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Nelmio\CorsBundle\Options\ProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the configuration for the nelmio/cors-bundle.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class WebsiteRootsConfigProvider implements ProviderInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * Constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(Request $request)
    {
        if (!$this->isCorsRequest($request) || !$this->canRunDbQuery()) {
            return [];
        }

        $stmt = $this->connection->prepare("SELECT id FROM tl_page WHERE type='root' AND dns=:dns");
        $stmt->bindValue('dns', preg_replace('@^https?://@', '', $request->headers->get('origin')));
        $stmt->execute();

        if (0 === $stmt->rowCount()) {
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
    private function isCorsRequest(Request $request)
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
    private function canRunDbQuery()
    {
        try {
            return $this->connection->isConnected() && $this->connection->getSchemaManager()->tablesExist(['tl_page']);
        } catch (ConnectionException $e) {
            return false;
        }
    }
}
