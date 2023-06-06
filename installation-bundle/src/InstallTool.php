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
use Contao\CoreBundle\Migration\MigrationCollection;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\File;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Mysqli\Driver as MysqliDriver;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class InstallTool
{
    private Connection $connection;
    private string $projectDir;
    private LoggerInterface $logger;
    private MigrationCollection $migrations;

    /**
     * @internal
     */
    public function __construct(Connection $connection, string $projectDir, LoggerInterface $logger, MigrationCollection $migrations)
    {
        $this->connection = $connection;
        $this->projectDir = $projectDir;
        $this->logger = $logger;
        $this->migrations = $migrations;
    }

    public function isLocked(): bool
    {
        $file = Path::join($this->projectDir, 'var/install_lock');

        if (!file_exists($file)) {
            return false;
        }

        $count = file_get_contents($file);

        return (int) $count >= 3;
    }

    public function canWriteFiles(): bool
    {
        return is_writable(__FILE__);
    }

    public function shouldAcceptLicense(): bool
    {
        return false;
    }

    public function increaseLoginCount(): void
    {
        $count = 0;
        $file = Path::join($this->projectDir, 'var/install_lock');

        if (file_exists($file)) {
            $count = file_get_contents($file);
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
            $this->connection->executeQuery('SHOW TABLES');

            return true;
        } catch (\Exception $e) {
        }

        if (null === $name) {
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
            $this->connection->executeStatement('USE '.$quotedName);
        } catch (Exception $e) {
            $this->logException($e);

            return false;
        }

        return true;
    }

    public function hasTable(string $name): bool
    {
        return $this->connection->createSchemaManager()->tablesExist([$name]);
    }

    public function isFreshInstallation(): bool
    {
        if (!$this->hasTable('tl_page')) {
            return true;
        }

        return $this->connection->fetchOne('SELECT COUNT(*) FROM tl_page') < 1;
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

        $columns = $this->connection->fetchAllAssociative($sql);

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
        [$version] = explode('-', $this->connection->fetchOne('SELECT @@version'));

        // The database version is too old
        if (version_compare($version, '5.1.0', '<')) {
            $context['errorCode'] = 1;
            $context['version'] = $version;

            return true;
        }

        $options = $this->connection->getParams()['defaultTableOptions'];

        // Check the collation if the user has configured it
        if (isset($options['collate'])) {
            $row = $this->connection->fetchAssociative("SHOW COLLATION LIKE '".$options['collate']."'");

            // The configured collation is not installed
            if (false === $row) {
                $context['errorCode'] = 2;
                $context['collation'] = $options['collate'];

                return true;
            }
        }

        // Check the engine if the user has configured it
        if (isset($options['engine'])) {
            $engineFound = false;
            $rows = $this->connection->fetchAllAssociative('SHOW ENGINES');

            foreach ($rows as $row) {
                if ($options['engine'] === $row['Engine']) {
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

            $row = $this->connection->fetchAssociative("SHOW VARIABLES LIKE 'innodb_large_prefix'");

            // The variable no longer exists as of MySQL 8 and MariaDB 10.3
            if (false === $row || '' === $row['Value']) {
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
            if (!\in_array(strtolower((string) $row['Value']), ['1', 'on'], true)) {
                $context['errorCode'] = 5;

                return true;
            }

            $row = $this->connection->fetchAssociative("SHOW VARIABLES LIKE 'innodb_file_per_table'");

            // The innodb_file_per_table option is disabled
            if (!\in_array(strtolower((string) $row['Value']), ['1', 'on'], true)) {
                $context['errorCode'] = 6;

                return true;
            }

            $row = $this->connection->fetchAssociative("SHOW VARIABLES LIKE 'innodb_file_format'");

            // The InnoDB file format is not Barracuda
            if ('' !== $row['Value'] && 'barracuda' !== strtolower((string) $row['Value'])) {
                $context['errorCode'] = 6;

                return true;
            }
        }

        return false;
    }

    /**
     * Checks if strict mode is enabled (see https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html).
     */
    public function checkStrictMode(array &$context): void
    {
        $mode = $this->connection->fetchOne('SELECT @@sql_mode');

        if (!array_intersect(explode(',', strtoupper($mode)), ['TRADITIONAL', 'STRICT_ALL_TABLES', 'STRICT_TRANS_TABLES'])) {
            $context['optionKey'] = $this->connection->getDriver() instanceof MysqliDriver ? 3 : 1002;
        }
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
     * @return array<string>
     */
    public function getTemplates(): array
    {
        /** @var array<SplFileInfo> $finder */
        $finder = Finder::create()
            ->files()
            ->name('*.sql')
            ->in(Path::join($this->projectDir, 'templates'))
        ;

        $templates = [];

        foreach ($finder as $file) {
            $templates[] = $file->getRelativePathname();
        }

        natcasesort($templates);

        return $templates;
    }

    public function importTemplate(string $template, bool $preserveData = false): void
    {
        if (!$preserveData) {
            $tables = $this->connection->createSchemaManager()->listTableNames();

            foreach ($tables as $table) {
                if (0 === strncmp($table, 'tl_', 3)) {
                    $this->connection->executeStatement('TRUNCATE TABLE '.$this->connection->quoteIdentifier($table));
                }
            }
        }

        $data = file(Path::join($this->projectDir, 'templates', $template));

        foreach (preg_grep('/^INSERT /', $data) as $query) {
            $this->connection->executeStatement($query);
        }
    }

    public function hasAdminUser(): bool
    {
        try {
            if ($this->connection->fetchOne("SELECT COUNT(*) FROM tl_user WHERE `admin` = '1'") > 0) {
                return true;
            }
        } catch (Exception $e) {
            // ignore
        }

        return false;
    }

    public function persistAdminUser(string $username, string $name, string $email, string $password, string $language): void
    {
        $replace = [
            '#' => '&#35;',
            '<' => '&#60;',
            '>' => '&#62;',
            '(' => '&#40;',
            ')' => '&#41;',
            '\\' => '&#92;',
            '=' => '&#61;',
        ];

        $this->connection->executeStatement(
            "
                INSERT INTO tl_user
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
            ",
            [
                'time' => time(),
                'name' => strtr($name, $replace),
                'email' => $email,
                'username' => strtr($username, $replace),
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'language' => $language,
            ]
        );
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

    public function runMigrations(): array
    {
        $messages = [];

        /** @var MigrationResult $migrationResult */
        foreach ($this->migrations->run() as $migrationResult) {
            $messages[] = $migrationResult->getMessage();
        }

        return $messages;
    }
}
