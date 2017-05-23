<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle;

use Contao\Backend;
use Contao\Config;
use Contao\Encryption;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
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
     * @var string
     */
    private $logDir;

    /**
     * Constructor.
     *
     * @param Connection $connection
     * @param string     $rootDir
     * @param string     $logDir
     */
    public function __construct(Connection $connection, $rootDir, $logDir)
    {
        $this->connection = $connection;
        $this->rootDir = $rootDir;
        $this->logDir = $logDir;
    }

    /**
     * Returns true if the install tool has been locked.
     *
     * @return bool
     */
    public function isLocked()
    {
        $cache = \System::getContainer()->get('contao.cache');

        if ($cache->contains('login-count')) {
            return (int) ($cache->fetch('login-count')) >= 3;
        }

        return false;
    }

    /**
     * Returns true if the install tool can write files.
     *
     * @return bool
     */
    public function canWriteFiles()
    {
        return is_writable(__FILE__);
    }

    /**
     * Checks if the license has been accepted.
     *
     * @return bool
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
        $cache = \System::getContainer()->get('contao.cache');

        if ($cache->contains('login-count')) {
            $count = (int) ($cache->fetch('login-count')) + 1;
        } else {
            $count = 1;
        }

        $cache->save('login-count', $count);
    }

    /**
     * Resets the login count.
     */
    public function resetLoginCount()
    {
        \File::putContent('system/tmp/login-count.txt', 0);
    }

    /**
     * Sets a database connection object.
     *
     * @param Connection $connection
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Checks if a database connection can be established.
     *
     * @param string $name
     *
     * @return bool
     */
    public function canConnectToDatabase($name)
    {
        if (null === $this->connection) {
            return false;
        }

        try {
            $this->connection->connect();
        } catch (\Exception $e) {
            return false;
        }

        $quotedName = $this->connection->quoteIdentifier($name);

        try {
            $this->connection->query('use '.$quotedName);
        } catch (DBALException $e) {
            return false;
        }

        return true;
    }

    /**
     * Checks if a table exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasTable($name)
    {
        return $this->connection->getSchemaManager()->tablesExist([$name]);
    }

    /**
     * Checks if the installation is fresh.
     *
     * @return bool
     */
    public function isFreshInstallation()
    {
        if (!$this->hasTable('tl_module')) {
            return true;
        }

        $statement = $this->connection->query('SELECT COUNT(*) AS count FROM tl_page');

        return $statement->fetch(\PDO::FETCH_OBJ)->count < 1;
    }

    /**
     * Checks if the database is older than version 3.2.
     *
     * @return bool
     */
    public function hasOldDatabase()
    {
        if (!$this->hasTable('tl_layout')) {
            return false;
        }

        $sql = $this->connection
            ->getDatabasePlatform()
            ->getListTableColumnsSQL('tl_layout', $this->connection->getDatabase())
        ;

        $column = $this->connection->fetchAssoc($sql." AND COLUMN_NAME = 'sections'");

        return 'varchar(1022)' !== $column['Type'];
    }

    /**
     * Handles executing the runonce files.
     */
    public function handleRunOnce()
    {
        // Wait for the tables to be created (see #5061)
        if (!$this->hasTable('tl_log')) {
            return;
        }

        Backend::handleRunOnce();
    }

    /**
     * Returns the available SQL templates.
     *
     * @return array
     */
    public function getTemplates()
    {
        $finder = Finder::create()
            ->files()
            ->name('*.sql')
            ->in($this->rootDir.'/templates')
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
     * @param string $template
     * @param bool   $preserveData
     */
    public function importTemplate($template, $preserveData = false)
    {
        if (!$preserveData) {
            $tables = $this->connection->getSchemaManager()->listTableNames();

            foreach ($tables as $table) {
                if (0 === strncmp($table, 'tl_', 3)) {
                    $this->connection->query('TRUNCATE TABLE '.$this->connection->quoteIdentifier($table));
                }
            }
        }

        $data = file($this->rootDir.'/templates/'.$template);

        foreach (preg_grep('/^INSERT /', $data) as $query) {
            $this->connection->query($query);
        }
    }

    /**
     * Checks if there is an admin user.
     *
     * @return bool
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
     * @param string $username
     * @param string $name
     * @param string $email
     * @param string $password
     * @param string $language
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
    }

    /**
     * Returns a Contao parameter.
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function getConfig($key)
    {
        return Config::get($key);
    }

    /**
     * Sets a Contao parameter.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setConfig($key, $value)
    {
        Config::set($key, $value);
    }

    /**
     * Persists a Contao parameter.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function persistConfig($key, $value)
    {
        $config = Config::getInstance();
        $config->persist($key, $value);
        $config->save();
    }

    /**
     * Logs an exception in the current log file.
     *
     * @param \Exception $e
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
            $this->logDir.'/prod-'.date('Y-m-d').'.log'
        );
    }
}
