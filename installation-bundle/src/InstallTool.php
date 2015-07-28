<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle;

use Contao\Config;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;

/**
 * Provides installation related methods.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InstallTool
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * Constructor.
     *
     * @param Connection $connection The database connection
     * @param string     $rootDir    The root directory
     */
    public function __construct(Connection $connection, $rootDir)
    {
        $this->connection = $connection;
        $this->rootDir = $rootDir;
    }

    /**
     * Returns true if the install tool has been locked.
     *
     * @return bool True if the install tool has been locked
     */
    public function isLocked()
    {
        return Config::get('installCount') >= 3;
    }

    /**
     * Returns true if the install tool can write files.
     *
     * @return bool True if the install tool can write files
     */
    public function canWriteFiles()
    {
        return is_writable(__FILE__);
    }

    /**
     * Creates the local configuration files if they do not yet exist.
     */
    public function createLocalConfigurationFiles()
    {
        // The localconfig.php file is created by the Config class
        foreach (['dcaconfig', 'initconfig', 'langconfig'] as $file) {
            if (!file_exists($this->rootDir . '/../system/config/' . $file . '.php')) {
                file_put_contents(
                    $this->rootDir . '/../system/config/' . $file . '.php',
                    '<?php' . "\n\n// Put your custom configuration here\n"
                );
            }
        }
    }

    /**
     * Checks if the license has been accepted.
     *
     * @return bool True if the license has not been accepted yet
     */
    public function shouldAcceptLicense()
    {
        return !Config::get('licenseAccepted');
    }

    /**
     * Increases the login count.
     */
    public function increaseLoginCount()
    {
        $this->persistConfig('installCount', $this->getConfig('installCount') + 1);
    }

    /**
     * Resets the login count.
     */
    public function resetLoginCount()
    {
        $this->persistConfig('installCount', 0);
    }

    /**
     * Sets a database connection object.
     *
     * @param Connection $connection The connection object
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Checks if a database connection can be established.
     *
     * @param string $name The database name
     *
     * @return bool True if a database connection can be established
     */
    public function canConnectToDatabase($name)
    {
        if (null === $this->connection) {
            return false;
        }

        try {
            $this->connection->connect();
        } catch (ConnectionException $e) {
            return false;
        }

        $quotedName = $this->connection->quoteIdentifier($name);

        try {
            $this->connection->query('use ' . $quotedName);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the installation is fresh.
     *
     * @return bool True if the installation is fresh.
     */
    public function isFreshInstallation()
    {
        if (!$this->connection->getSchemaManager()->tablesExist('tl_module')) {
            return true;
        }

        $statement = $this->connection->query('SELECT COUNT(*) AS count FROM tl_page');

        if ($statement->fetch(\PDO::FETCH_OBJ)->count < 1) {
            return true;
        }

        return false;
    }

    /**
     * Returns a Contao parameter.
     *
     * @param string $key The key
     *
     * @return mixed|null The value
     */
    public function getConfig($key)
    {
        return Config::get($key);
    }

    /**
     * Sets a Contao parameter.
     *
     * @param string $key   The key
     * @param mixed  $value The value
     */
    public function setConfig($key, $value)
    {
        Config::set($key, $value);
    }

    /**
     * Persists a Contao parameter.
     *
     * @param string $key   The key
     * @param mixed  $value The value
     */
    public function persistConfig($key, $value)
    {
        $config = Config::getInstance();
        $config->persist($key, $value);
        $config->save();
    }
}
