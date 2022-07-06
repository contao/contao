<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\Schema;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database\Installer;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;

class DcaSchemaProvider
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var int|null
     */
    private $defaultIndexLength;

    /**
     * @internal Do not inherit from this class; decorate the "contao.doctrine.schema_provider" service instead
     */
    public function __construct(ContaoFramework $framework, Registry $doctrine)
    {
        $this->framework = $framework;
        $this->doctrine = $doctrine;
    }

    public function createSchema(): Schema
    {
        if (0 !== \count($this->doctrine->getManagerNames())) {
            return $this->createSchemaFromOrm();
        }

        return $this->createSchemaFromDca();
    }

    /**
     * Adds the DCA data to the Doctrine schema.
     */
    public function appendToSchema(Schema $schema): void
    {
        $this->defaultIndexLength = null;

        $config = $this->getSqlDefinitions();

        foreach ($config as $tableName => $definitions) {
            $table = $schema->hasTable($tableName) ? $schema->getTable($tableName) : $schema->createTable($tableName);

            // Parse the table options first
            if (isset($definitions['TABLE_OPTIONS'])) {
                if (preg_match('/ENGINE=([^ ]+)/i', $definitions['TABLE_OPTIONS'], $match)) {
                    $table->addOption('engine', $match[1]);
                }

                if (preg_match('/DEFAULT CHARSET=([^ ]+)/i', $definitions['TABLE_OPTIONS'], $match)) {
                    $table->addOption('charset', $match[1]);
                    $table->addOption('collate', $match[1].'_general_ci');
                }

                if (preg_match('/COLLATE ([^ ]+)/i', $definitions['TABLE_OPTIONS'], $match)) {
                    $table->addOption('collate', $match[1]);
                }
            }

            if (isset($definitions['SCHEMA_FIELDS'])) {
                foreach ($definitions['SCHEMA_FIELDS'] as $fieldName => $config) {
                    if ($table->hasColumn($fieldName)) {
                        continue;
                    }

                    $options = $config;
                    unset($options['name'], $options['type']);

                    // Use the binary collation if the "case_sensitive" option is set
                    if ($this->isCaseSensitive($config)) {
                        $options['platformOptions']['collation'] = $this->getBinaryCollation($table);
                    }

                    if (isset($options['customSchemaOptions']['charset'])) {
                        $options['platformOptions']['charset'] = $options['customSchemaOptions']['charset'];
                    }

                    if (isset($options['customSchemaOptions']['collation'])) {
                        if (!isset($options['customSchemaOptions']['charset'])) {
                            $options['platformOptions']['charset'] = explode('_', $options['customSchemaOptions']['collation'], 2)[0];
                        }

                        $options['platformOptions']['collation'] = $options['customSchemaOptions']['collation'];
                    }

                    $table->addColumn($config['name'], $config['type'], $options);
                }
            }

            if (isset($definitions['TABLE_FIELDS'])) {
                foreach ($definitions['TABLE_FIELDS'] as $fieldName => $sql) {
                    if ($table->hasColumn($fieldName)) {
                        continue;
                    }

                    $this->parseColumnSql($table, $fieldName, substr($sql, \strlen($fieldName) + 3));
                }
            }

            if (isset($definitions['TABLE_CREATE_DEFINITIONS'])) {
                foreach ($definitions['TABLE_CREATE_DEFINITIONS'] as $keyName => $sql) {
                    if ($table->hasIndex($keyName)) {
                        continue;
                    }

                    $this->parseIndexSql($table, $keyName, strtolower($sql));
                }
            }
        }
    }

    private function createSchemaFromOrm(): Schema
    {
        /** @var EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        /** @var array<ClassMetadata> $metadata */
        $metadata = $manager->getMetadataFactory()->getAllMetadata();

        /** @var Connection $connection */
        $connection = $this->doctrine->getConnection();

        // Apply the schema filter
        if ($filter = $connection->getConfiguration()->getSchemaAssetsFilter()) {
            foreach ($metadata as $key => $data) {
                if (!$filter($data->getTableName())) {
                    unset($metadata[$key]);
                }
            }
        }

        if (empty($metadata)) {
            return $this->createSchemaFromDca();
        }

        $tool = new SchemaTool($manager);

        return $tool->getSchemaFromMetadata($metadata);
    }

    private function createSchemaFromDca(): Schema
    {
        $config = new SchemaConfig();
        $params = $this->doctrine->getConnection()->getParams();

        if (isset($params['defaultTableOptions'])) {
            $config->setDefaultTableOptions($params['defaultTableOptions']);
        }

        $schema = new Schema([], [], $config);

        $this->appendToSchema($schema);

        return $schema;
    }

    private function parseColumnSql(Table $table, string $columnName, string $sql): void
    {
        $chunks = explode(' ', $sql, 2);
        $dbType = $chunks[0];
        $def = $chunks[1] ?? null;

        $type = strtok(strtolower($dbType), '(), ');
        $length = (int) strtok('(), ');

        $fixed = false;
        $scale = null;
        $precision = null;
        $default = null;
        $charset = null;
        $collation = null;
        $unsigned = false;
        $notnull = false;
        $autoincrement = false;

        $this->setLengthAndPrecisionByType($type, $dbType, $length, $scale, $precision, $fixed);

        $connection = $this->doctrine->getConnection();
        $type = $connection->getDatabasePlatform()->getDoctrineTypeMapping($type);

        if (0 === $length) {
            $length = null;
        }

        if (null !== $def) {
            if (preg_match('/default (\'[^\']*\'|\d+(?:\.\d+)?)/i', $def, $match)) {
                if (is_numeric($match[1])) {
                    $default = $match[1] * 1;
                } else {
                    $default = trim($match[1], "'");
                }
            }

            if (preg_match('/collate ([^ ]+)/i', $def, $match)) {
                $charset = explode('_', $match[1], 2)[0];
                $collation = $match[1];
            }

            // Use the binary collation if the BINARY flag is set (see #1286)
            if (0 === strncasecmp($def, 'binary ', 7)) {
                $collation = $this->getBinaryCollation($table);
            }

            $unsigned = false !== stripos($def, 'unsigned');
            $notnull = false !== stripos($def, 'not null');
            $autoincrement = false !== stripos($def, 'auto_increment');
        }

        $options = [
            'length' => $length,
            'unsigned' => $unsigned,
            'fixed' => $fixed,
            'default' => $default,
            'notnull' => $notnull,
            'scale' => null,
            'precision' => null,
            'autoincrement' => $autoincrement,
            'comment' => null,
        ];

        if (null !== $scale && null !== $precision) {
            $options['scale'] = $scale;
            $options['precision'] = $precision;
        }

        $platformOptions = [];

        if (null !== $charset) {
            $platformOptions['charset'] = $charset;
        }

        if (null !== $collation) {
            $platformOptions['collation'] = $collation;
        }

        if (!empty($platformOptions)) {
            $options['platformOptions'] = $platformOptions;
        }

        $table->addColumn($columnName, $type, $options);
    }

    private function setLengthAndPrecisionByType(string $type, string $dbType, ?int &$length, ?int &$scale, ?int &$precision, bool &$fixed): void
    {
        switch ($type) {
            case 'char':
            case 'binary':
                $fixed = true;
                break;

            case 'float':
            case 'double':
            case 'real':
            case 'numeric':
            case 'decimal':
                if (preg_match('/[a-z]+\((\d+),(\d+)\)/i', $dbType, $match)) {
                    $length = null;
                    [, $precision, $scale] = $match;
                }
                break;

            case 'tinytext':
                $length = MySqlPlatform::LENGTH_LIMIT_TINYTEXT;
                break;

            case 'text':
                $length = MySqlPlatform::LENGTH_LIMIT_TEXT;
                break;

            case 'mediumtext':
                $length = MySqlPlatform::LENGTH_LIMIT_MEDIUMTEXT;
                break;

            case 'tinyblob':
                $length = MySqlPlatform::LENGTH_LIMIT_TINYBLOB;
                break;

            case 'blob':
                $length = MySqlPlatform::LENGTH_LIMIT_BLOB;
                break;

            case 'mediumblob':
                $length = MySqlPlatform::LENGTH_LIMIT_MEDIUMBLOB;
                break;

            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'integer':
            case 'bigint':
            case 'year':
                $length = null;
                break;
        }
    }

    private function parseIndexSql(Table $table, string $keyName, string $sql): void
    {
        if ('PRIMARY' === $keyName) {
            if (!preg_match_all('/`([^`]+)`/', $sql, $matches)) {
                throw new \RuntimeException(sprintf('Primary key definition "%s" could not be parsed.', $sql));
            }

            $table->setPrimaryKey($matches[1]);

            return;
        }

        if (!preg_match('/(.*) `([^`]+)` \((.*)\)/', $sql, $matches)) {
            throw new \RuntimeException(sprintf('Key definition "%s" could not be parsed.', $sql));
        }

        $columns = [];
        $flags = [];
        $lengths = [];

        foreach (explode(',', $matches[3]) as $column) {
            preg_match('/`([^`]+)`(\((\d+)\))?/', $column, $cm);

            $columns[] = $cm[1];
            $lengths[] = isset($cm[3]) ? (int) $cm[3] : $this->getIndexLength($table, $cm[1]);
        }

        if (false !== strpos($matches[1], 'unique')) {
            $table->addUniqueIndex($columns, $matches[2]);
        } else {
            if (false !== strpos($matches[1], 'fulltext')) {
                $flags[] = 'fulltext';
            }

            $table->addIndex($columns, $matches[2], $flags, ['lengths' => $lengths]);
        }
    }

    /**
     * Returns the SQL definitions from the Contao installer.
     *
     * @return array<string, array<string, array<string>>>
     */
    private function getSqlDefinitions(): array
    {
        $this->framework->initialize();

        /** @var Installer $installer */
        $installer = $this->framework->createInstance(Installer::class);
        $sqlTarget = $installer->getFromDca();
        $sqlLegacy = $installer->getFromFile();

        // Manually merge the legacy definitions (see #4766)
        if (!empty($sqlLegacy)) {
            foreach ($sqlLegacy as $table => $categories) {
                foreach ($categories as $category => $fields) {
                    if (\is_array($fields)) {
                        foreach ($fields as $name => $sql) {
                            $sqlTarget[$table][$category][$name] = $sql;
                        }
                    } else {
                        $sqlTarget[$table][$category] = $fields;
                    }
                }
            }
        }

        /** @var Connection $connection */
        $connection = $this->doctrine->getConnection();

        // Apply the schema filter (see contao/installation-bundle#78)
        if ($filter = $connection->getConfiguration()->getSchemaAssetsFilter()) {
            foreach (array_keys($sqlTarget) as $key) {
                if (!$filter($key)) {
                    unset($sqlTarget[$key]);
                }
            }
        }

        return $sqlTarget;
    }

    /**
     * Returns the index length if the index needs to be shortened.
     */
    private function getIndexLength(Table $table, string $column): ?int
    {
        $col = $table->getColumn($column);

        // Not a text field
        if (null === ($length = $col->getLength())) {
            return null;
        }

        // Return if the field is shorter than the shortest possible index
        // length (utf8mb4 on InnoDB without large prefixes)
        if ($length <= 191) {
            return null;
        }

        if ($col->hasPlatformOption('collation')) {
            $collation = $col->getPlatformOption('collation');
        } else {
            $collation = $table->getOption('collate');
        }

        $defaultLength = $this->getDefaultIndexLength($table);
        $bytes = 0 === strncmp($collation, 'utf8mb4', 7) ? 4 : 3;
        $indexLength = (int) floor($defaultLength / $bytes);

        // Return if the field is shorter than the index length
        if ($length <= $indexLength) {
            return null;
        }

        return $indexLength;
    }

    private function getDefaultIndexLength(Table $table): int
    {
        $engine = $table->getOption('engine');

        if ('innodb' !== strtolower($engine)) {
            return 1000;
        }

        if (null !== $this->defaultIndexLength) {
            return $this->defaultIndexLength;
        }

        $largePrefix = $this->doctrine
            ->getConnection()
            ->query("SHOW VARIABLES LIKE 'innodb_large_prefix'")
            ->fetch(\PDO::FETCH_OBJ)
        ;

        // The variable no longer exists as of MySQL 8 and MariaDB 10.3
        if (false === $largePrefix || '' === $largePrefix->Value) {
            return $this->defaultIndexLength = 3072;
        }

        $version = $this->doctrine
            ->getConnection()
            ->query('SELECT @@version as Value')
            ->fetch(\PDO::FETCH_OBJ)
        ;

        [$ver] = explode('-', $version->Value);

        // As there is no reliable way to get the vendor (see #84), we are
        // guessing based on the version number. The check will not be run
        // as of MySQL 8 and MariaDB 10.3, so this should be safe.
        $vok = version_compare($ver, '10', '>=') ? '10.2.2' : '5.7.7';

        // Large prefixes are always enabled as of MySQL 5.7.7 and MariaDB 10.2.2
        if (version_compare($ver, $vok, '>=')) {
            return $this->defaultIndexLength = 3072;
        }

        // The innodb_large_prefix option is disabled
        if (!\in_array(strtolower((string) $largePrefix->Value), ['1', 'on'], true)) {
            return $this->defaultIndexLength = 767;
        }

        $filePerTable = $this->doctrine
            ->getConnection()
            ->query("SHOW VARIABLES LIKE 'innodb_file_per_table'")
            ->fetch(\PDO::FETCH_OBJ)
        ;

        // The innodb_file_per_table option is disabled
        if (!\in_array(strtolower((string) $filePerTable->Value), ['1', 'on'], true)) {
            return $this->defaultIndexLength = 767;
        }

        $fileFormat = $this->doctrine
            ->getConnection()
            ->query("SHOW VARIABLES LIKE 'innodb_file_format'")
            ->fetch(\PDO::FETCH_OBJ)
        ;

        // The InnoDB file format is not Barracuda
        if ('' !== $fileFormat->Value && 'barracuda' !== strtolower((string) $fileFormat->Value)) {
            return $this->defaultIndexLength = 767;
        }

        return $this->defaultIndexLength = 3072;
    }

    private function isCaseSensitive(array $config): bool
    {
        if (!isset($config['customSchemaOptions']['case_sensitive'])) {
            return false;
        }

        return true === $config['customSchemaOptions']['case_sensitive'];
    }

    /**
     * Returns the binary collation depending on the charset.
     */
    private function getBinaryCollation(Table $table): ?string
    {
        if (!$table->hasOption('charset')) {
            return null;
        }

        return $table->getOption('charset').'_bin';
    }
}
