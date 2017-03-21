<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Cors;

use Doctrine\DBAL\Connection;
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
        if (!$request->headers->has('Origin') || '' === $request->headers->get('Origin')) {
            return [];
        }

        $stmt = $this->connection->prepare('SELECT id FROM tl_page WHERE type=:type AND dns=:dns');
        $stmt->bindValue('type', 'root');
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
}
