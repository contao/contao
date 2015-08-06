<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle;

use Contao\Backend;
use Contao\Config;
use Contao\Encryption;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\ConnectionException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

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
        } catch (DBALException $e) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the installation is fresh.
     *
     * @return bool True if the installation is fresh
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
     * Checks if the database is older than version 3.2.
     *
     * @return bool True if the database is older than version 3.2
     */
    public function hasOldDatabase()
    {
        if (!$this->connection->getSchemaManager()->tablesExist('tl_layout')) {
            return false;
        }

        $sql = $this->connection
            ->getDatabasePlatform()
            ->getListTableColumnsSQL('tl_layout', $this->connection->getDatabase())
        ;

        $column = $this->connection->fetchAssoc($sql . " AND COLUMN_NAME = 'sections'");

        return 'varchar(1022)' !== $column['Type'];
    }

    /**
     * Handles executing the runonce files.
     */
    public function handleRunOnce()
    {
        // Wait for the tables to be created (see #5061)
        if (!$this->connection->getSchemaManager()->tablesExist('tl_log')) {
            return;
        }

        Backend::handleRunOnce();
    }

    /**
     * Returns the available SQL templates.
     *
     * @return array The SQL templates
     */
    public function getTemplates()
    {
        $finder = Finder::create()
            ->files()
            ->name('*.sql')
            ->in($this->rootDir . '/../templates')
        ;

        $templates = [];

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $templates[] = $file->getRelativePathname();
        }

        return $templates;
    }

    /**
     * Imports a template.
     *
     * @param string $template     The template path
     * @param bool   $preserveData True to preserve the existing data
     */
    public function importTemplate($template, $preserveData = false)
    {
        if (!$preserveData) {
            $tables = $this->connection->getSchemaManager()->listTableNames();

            foreach ($tables as $table) {
                if (0 === strncmp($table, 'tl_', 3)) {
                    $this->connection->query('TRUNCATE TABLE ' . $this->connection->quoteIdentifier($table));
                }
            }
        }

        $data = file($this->rootDir . '/../templates/' . $template);

        foreach (preg_grep('/^INSERT /', $data) as $query) {
            $this->connection->query($query);
        }
    }

    /**
     * Checks if there is an admin user.
     *
     * @return bool True if there is an admin user
     */
    public function hasAdminUser()
    {
        try {
            $statement = $this->connection->query('SELECT COUNT(*) AS count FROM tl_user WHERE admin=1');

            if ($statement->fetch(\PDO::FETCH_OBJ)->count > 0) {
                return true;
            }
        } catch (DBALException $e) {
            // ignore
        }

        return false;
    }

    /**
     * Persists the admin user.
     *
     * @param string $username The username
     * @param string $name     The name
     * @param string $email    The e-mail address
     * @param string $password The plain text password
     * @param string $language The language
     */
    public function persistAdminUser($username, $name, $email, $password, $language)
    {
        $statement = $this->connection->prepare("
            INSERT INTO tl_user(
                tstamp,
                name,
                email,
                username,
                password,
                language,
                backendTheme,
                admin,
                showHelp,
                useRTE,
                useCE,
                thumbnails,
                dateAdded
            ) VALUES (
                :time,
                :name,
                :email,
                :username,
                :password,
                :language,
                'flexible',
                1,
                1,
                1,
                1,
                1,
                :time
            )
        ");

        $replace = [
            '#' => '&#35;',
            '<' => '&#60;',
            '>' => '&#62;',
            '(' => '&#40;',
            ')' => '&#41;',
            '\\' => '&#92;',
            '=' => '&#61;',
        ];

        $statement->execute([
            ':time' => time(),
            ':name' => strtr($name, $replace),
            ':email' => $email,
            ':username' => strtr($username, $replace),
            ':password' => Encryption::hash($password),
            ':language' => $language,
        ]);

        $this->persistConfig('adminEmail', $email);
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

    /**
     * Logs an exception in the error.log file.
     *
     * @param \Exception $e The exception
     */
    public function logException(\Exception $e)
    {
        error_log(
            sprintf(
                "PHP Fatal error: %s in %s on line %s\n%s\n",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            ),
            3,
            $this->rootDir . '/logs/error.log'
        );
    }
}
