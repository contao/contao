<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle;

use Contao\Backend;
use Contao\Config;
use Contao\File;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;
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
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Connection $connection, string $rootDir, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->rootDir = $rootDir;
        $this->logger = $logger;
    }

    public function isLocked(): bool
    {
        $file = $this->rootDir.'/var/install_lock';

        if (!file_exists($file)) {
            return false;
        }

        $count = file_get_contents($this->rootDir.'/var/install_lock');

        return (int) $count >= 3;
    }

    public function canWriteFiles(): bool
    {
        return is_writable(__FILE__);
    }

    public function shouldAcceptLicense(): bool
    {
        return !Config::get('licenseAccepted');
    }

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

    public function resetLoginCount(): void
    {
        File::putContent('system/tmp/login-count.txt', 0);
    }

    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    public function canConnectToDatabase(?string $name): bool
    {
        // Return if there is a working database connection already
        try {
            $this->connection->connect();
            $this->connection->query('SHOW TABLES');

            return true;
        } catch (\Exception $e) {
        }

        if (null === $name || null === $this->connection) {
            return false;
        }

        try {
            $this->connection->connect();
        } catch (\Exception $e) {
            $this->logException($e);

            return false;
        }

        $quotedName = $this->connection->quoteIdentifier($name);

        try {
            $this->connection->query('use '.$quotedName);
        } catch (DBALException $e) {
            $this->logException($e);

            return false;
        }

        return true;
    }

    public function hasTable(string $name): bool
    {
        return $this->connection->getSchemaManager()->tablesExist([$name]);
    }

    public function isFreshInstallation(): bool
    {
        if (!$this->hasTable('tl_page')) {
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

        $columns = $this->connection->fetchAll($sql);

        foreach ($columns as $column) {
            if ('sections' === $column['Field']) {
                return !\in_array($column['Type'], ['varchar(1022)', 'blob'], true);
            }
        }

        return false;
    }

    /**
     * Checks the database configuration.
     */
    public function hasConfigurationError(array &$context): bool
    {
        $row = $this->connection
            ->query('SELECT @@version as Version')
            ->fetch(\PDO::FETCH_OBJ)
        ;

        [$version] = explode('-', $row->Version);

        // The database version is too old
        if (version_compare($version, '5.1.0', '<')) {
            $context['errorCode'] = 1;
            $context['version'] = $version;

            return true;
        }

        $options = $this->connection->getParams()['defaultTableOptions'];

        // Check the collation if the user has configured it
        if (isset($options['collate'])) {
            $statement = $this->connection->query("SHOW COLLATION LIKE '".$options['collate']."'");

            // The configured collation is not installed
            if (false === ($row = $statement->fetch(\PDO::FETCH_OBJ))) {
                $context['errorCode'] = 2;
                $context['collation'] = $options['collate'];

                return true;
            }
        }

        // Check the engine if the user has configured it
        if (isset($options['engine'])) {
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
        }

        // Check if utf8mb4 can be used if the user has configured it
        if (isset($options['engine'], $options['collate']) && 0 === strncmp($options['collate'], 'utf8mb4', 7)) {
            if ('innodb' !== strtolower($options['engine'])) {
                $context['errorCode'] = 4;
                $context['engine'] = $options['engine'];

                return true;
            }

            $row = $this->connection
                ->query("SHOW VARIABLES LIKE 'innodb_large_prefix'")
                ->fetch(\PDO::FETCH_OBJ)
            ;

            // The variable no longer exists as of MySQL 8 and MariaDB 10.3
            if (false === $row) {
                return false;
            }

            // As there is no reliable way to get the vendor (see #84), we are
            // guessing based on the version number. The check will not be run
            // as of MySQL 8 and MariaDB 10.3, so this should be safe.
            $vok = version_compare($version, '10', '>=') ? '10.2.2' : '5.7.7';

            // Large prefixes are always enabled as of MySQL 5.7.7 and MariaDB 10.2.2
            if (version_compare($version, $vok, '>=')) {
                return false;
            }

            // The innodb_large_prefix option is disabled
            if (!\in_array(strtolower((string) $row->Value), ['1', 'on'], true)) {
                $context['errorCode'] = 5;

                return true;
            }

            $row = $this->connection
                ->query("SHOW VARIABLES LIKE 'innodb_file_per_table'")
                ->fetch(\PDO::FETCH_OBJ)
            ;

            // The innodb_file_per_table option is disabled
            if (!\in_array(strtolower((string) $row->Value), ['1', 'on'], true)) {
                $context['errorCode'] = 6;

                return true;
            }

            $row = $this->connection
                ->query("SHOW VARIABLES LIKE 'innodb_file_format'")
                ->fetch(\PDO::FETCH_OBJ)
            ;

            // The InnoDB file format is not Barracuda
            if ('barracuda' !== strtolower((string) $row->Value)) {
                $context['errorCode'] = 6;

                return true;
            }
        }

        return false;
    }

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
     * @return string[]
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

    public function hasAdminUser(): bool
    {
        try {
            $statement = $this->connection->query("
                SELECT
                    COUNT(*) AS count
                FROM
                    tl_user
                WHERE
                    `admin` = '1'
            ");

            if ($statement->fetch(\PDO::FETCH_OBJ)->count > 0) {
                return true;
            }
        } catch (DBALException $e) {
            // ignore
        }

        return false;
    }

    public function persistAdminUser(string $username, string $name, string $email, string $password, string $language): void
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
                        `admin`,
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
     * @return mixed|null
     */
    public function getConfig(string $key)
    {
        return Config::get($key);
    }

    public function setConfig(string $key, $value): void
    {
        Config::set($key, $value);
    }

    public function persistConfig(string $key, $value): void
    {
        $config = Config::getInstance();
        $config->persist($key, $value);
        $config->save();
    }

    public function logException(\Exception $e): void
    {
        $this->logger->critical('An exception occurred.', ['exception' => $e]);
    }
}
