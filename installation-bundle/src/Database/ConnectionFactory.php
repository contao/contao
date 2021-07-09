<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;

class ConnectionFactory
{
    /**
     * Returns the database connection object.
     */
    public static function create(array $parameters): ?Connection
    {
        $params = [
            'driver' => 'pdo_mysql',
            'host' => $parameters['parameters']['database_host'],
            'port' => $parameters['parameters']['database_port'],
            'user' => $parameters['parameters']['database_user'],
            'password' => $parameters['parameters']['database_password'],
            'dbname' => $parameters['parameters']['database_name'],
        ];

        try {
            return DriverManager::getConnection($params);
        } catch (Exception $e) {
            // ignore
        }

        return null;
    }
}
