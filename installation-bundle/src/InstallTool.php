<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle;

use Contao\Backend;
use Contao\Config;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

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
     * @param Connection $connection
     * @param string     $rootDir
     * @param string     $logDir
     */
    public function __construct(Connection $connection, string $rootDir, string $logDir)
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
    public function isLocked(): bool
    {
        $file = $this->rootDir.'/var/install_lock';

        if (!file_exists($file)) {
            return false;
        }

        $count = file_get_contents($this->rootDir.'/var/install_lock');

        return (int) $count >= 3;
    }

    /**
     * Returns true if the install tool can write files.
     *
     * @return bool
     */
    public function canWriteFiles(): bool
    {
        return is_writable(__FILE__);
    }

    /**
     * Checks if the license has been accepted.
     *
     * @return bool
     */
    public function shouldAcceptLicense(): bool
    {
        return !Config::get('licenseAccepted');
    }

    /**
     * Increases the login count.
     */
    public function increaseLoginCount(): void
    {
        $count = 0;
        $file = $this->rootDir.'/var/install_lock';

        if (file_exists($file)) {
            $count = file_get_contents($this->rootDir.'/var/install_lock');
        }

        $fs = new Filesystem();
        $fs->dumpFile($file, (int) $count + 1);
    }

    /**
     * Resets the login count.
     */
    public function resetLoginCount(): void
    {
        \File::putContent('system/tmp/login-count.txt', 0);
    }

    /**
     * Sets a database connection object.
     *
     * @param Connection $connection
     */
    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * Checks if a database connection can be established.
     *
     * @param string|null $name
     *
     * @return bool
     */
    public function canConnectToDatabase(?string $name): bool
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
    public function hasTable(string $name): bool
    {
        return $this->connection->getSchemaManager()->tablesExist([$name]);
    }

    /**
     * Checks if the installation is fresh.
     *
     * @return bool
     */
    public function isFreshInstallation(): bool
    {
        if (!$this->hasTable('tl_module')) {
            return true;
        }

        $statement = $this->connection->query('
            SELECT
                COUNT(*) AS count
            FROM
                tl_page
        ');

        return $statement->fetch(\PDO::FETCH_OBJ)->count < 1;
    }

    /**
     * Checks if the database is older than version 3.2.
     *
     * @return bool
     */
    public function hasOldDatabase(): bool
    {
        if (!$this->hasTable('tl_layout')) {
            return false;
        }

        $sql = $this->connection
            ->getDatabasePlatform()
            ->getListTableColumnsSQL('tl_layout', $this->connection->getDatabase())
        ;

        $column = $this->connection->fetchAssoc($sql." AND COLUMN_NAME = 'sections'");

        return !\in_array($column['Type'], ['varchar(1022)', 'blob'], true);
    }

    /**
     * Checks the database configuration.
     *
     * @param array $context
     *
     * @return bool
     */
    public function hasConfigurationError(array &$context): bool
    {
        $row = $this->connection
            ->query('SELECT @@version as Version')
            ->fetch(\PDO::FETCH_OBJ)
        ;

        [$version] = explode('-', $row->Version);

        // The database version is too old
        if (version_compare($version, '5.5.7', '<')) {
            $context['errorCode'] = 1;
            $context['version'] = $version;

            return true;
        }

        $options = $this->connection->getParams()['defaultTableOptions'];
        $statement = $this->connection->query("SHOW COLLATION LIKE '".$options['collate']."'");

        // The configured collation is not installed
        if (false === ($row = $statement->fetch(\PDO::FETCH_OBJ))) {
            $context['errorCode'] = 2;
            $context['collation'] = $options['collate'];

            return true;
        }

        $engineFound = false;
        $statement = $this->connection->query('SHOW ENGINES');

        while (false !== ($row = $statement->fetch(\PDO::FETCH_OBJ))) {
            if ($options['engine'] === $row->Engine) {
                $engineFound = true;
                break;
            }
        }

        // The configured engine is not available
        if (!$engineFound) {
            $context['errorCode'] = 3;
            $context['engine'] = $options['engine'];

            return true;
        }

        if ('InnoDB' === $options['engine'] && 0 === strncmp($options['collate'], 'utf8mb4', 7)) {
            $row = $this->connection
                ->query("SHOW VARIABLES LIKE 'innodb_large_prefix'")
                ->fetch(\PDO::FETCH_OBJ)
            ;

            // The innodb_large_prefix option is not set
            if (!\in_array(strtolower((string) $row->Value), ['1', 'on'], true)) {
                $context['errorCode'] = 4;

                return true;
            }

            // As there is no reliable way to get the vendor (see #84), we are
            // guessing based on the version number. MySQL is currently at 8 so
            // checking for 10 should be save for the next couple of years.
            $vok = version_compare($version, '10', '>=') ? '10.2' : '5.7.7';

            // No additional requirements as of MySQL 5.7.7 and MariaDB 10.2
            if (version_compare($version, $vok, '>=')) {
                return false;
            }

            $row = $this->connection
                ->query("SHOW VARIABLES LIKE 'innodb_file_format'")
                ->fetch(\PDO::FETCH_OBJ)
            ;

            // The InnoDB file format is not Barracuda
            if ('barracuda' !== strtolower((string) $row->Value)) {
                $context['errorCode'] = 5;

                return true;
            }

            $row = $this->connection
                ->query("SHOW VARIABLES LIKE 'innodb_file_per_table'")
                ->fetch(\PDO::FETCH_OBJ)
            ;

            // The innodb_file_per_table option is not set
            if (!\in_array(strtolower((string) $row->Value), ['1', 'on'], true)) {
                $context['errorCode'] = 5;

                return true;
            }
        }

        return false;
    }

    /**
     * Handles executing the runonce files.
     */
    public function handleRunOnce(): void
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
    public function getTemplates(): array
    {
        /** @var SplFileInfo[] $finder */
        $finder = Finder::create()
            ->files()
            ->name('*.sql')
            ->in($this->rootDir.'/templates')
        ;

        $templates = [];

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
    public function importTemplate(string $template, bool $preserveData = false): void
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
    public function hasAdminUser(): bool
    {
        try {
            $statement = $this->connection->query("
                SELECT
                    COUNT(*) AS count
                FROM
                    tl_user
                WHERE
                    admin = '1'
            ");

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
    public function persistAdminUser($username, string $name, string $email, string $password, string $language): void
    {
        $statement = $this->connection->prepare("
            INSERT INTO
                tl_user
                    (
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
                    )
                 VALUES
                    (:time, :name, :email, :username, :password, :language, 'flexible', 1, 1, 1, 1, 1, :time)
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
            ':password' => password_hash($password, PASSWORD_DEFAULT),
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
    public function getConfig(string $key)
    {
        return Config::get($key);
    }

    /**
     * Sets a Contao parameter.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setConfig(string $key, $value): void
    {
        Config::set($key, $value);
    }

    /**
     * Persists a Contao parameter.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function persistConfig(string $key, $value): void
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
    public function logException(\Exception $e): void
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
