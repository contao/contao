<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Doctrine\Schema;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class DcaSchemaProvider
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function createSchema()
    {
        $schema = new Schema();

        $this->appendToSchema($schema);

        return $schema;
    }

    /**
     * Adds the DCA data to the Doctrine schema.
     *
     * @param Schema $schema
     */
    public function appendToSchema(Schema $schema)
    {
        $config = $this->getSqlDefinitions();

        foreach ($config as $tableName => $definitions) {
            $table = $schema->createTable($tableName);

            if (isset($definitions['SCHEMA_FIELDS'])) {
                foreach ($definitions['SCHEMA_FIELDS'] as $fieldName => $config) {
                    $options = $config;
                    unset($options['name'], $options['type']);
                    $table->addColumn($config['name'], $config['type'], $options);
                }
            }

            if (isset($definitions['TABLE_FIELDS'])) {
                foreach ($definitions['TABLE_FIELDS'] as $fieldName => $sql) {
                    $this->parseColumnSql($table, $fieldName, strtolower(substr($sql, strlen($fieldName) + 3)));
                }
            }

            if (isset($definitions['TABLE_CREATE_DEFINITIONS'])) {
                foreach ($definitions['TABLE_CREATE_DEFINITIONS'] as $keyName => $sql) {
                    $this->parseIndexSql($table, $keyName, strtolower($sql));
                }
            }

            if (isset($definitions['TABLE_OPTIONS'])) {
                if (preg_match('/ENGINE=([^ ]+)/i', $definitions['TABLE_OPTIONS'], $match)) {
                    $table->addOption('engine', $match[1]);
                }

                if (preg_match('/DEFAULT CHARSET=([^ ]+)/i', $definitions['TABLE_OPTIONS'], $match)) {
                    $table->addOption('charset', $match[1]);
                }
            }
        }
    }

    /**
     * Parses the column definition and adds it to the schema table.
     *
     * @param Table  $table
     * @param string $columnName
     * @param string $sql
     */
    private function parseColumnSql(Table $table, $columnName, $sql)
    {
        list($dbType, $def) = explode(' ', $sql, 2);

        $type = strtok(strtolower($dbType), '(), ');
        $length = strtok('(), ');

        $fixed = null;
        $scale = null;
        $precision = null;
        $default = null;

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
                if (preg_match('([A-Za-z]+\(([0-9]+)\,([0-9]+)\))', $dbType, $match)) {
                    $precision = $match[1];
                    $scale = $match[2];
                    $length = null;
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

        $type = $this->container->get('database_connection')->getDatabasePlatform()->getDoctrineTypeMapping($type);
        $length = (0 === (int) $length) ? null : (int) $length;

        if (preg_match('/default (\'[^\']*\'|\d+)/', $def, $match)) {
            $default = trim($match[1], "'");
        }

        $options = [
            'length' => $length,
            'unsigned' => false !== strpos($def, 'unsigned'),
            'fixed' => (bool) $fixed,
            'default' => $default,
            'notnull' => false !== strpos($def, 'not null'),
            'scale' => null,
            'precision' => null,
            'autoincrement' => false !== strpos($def, 'auto_increment'),
            'comment' => null,
        ];

        if (null !== $scale && null !== $precision) {
            $options['scale'] = $scale;
            $options['precision'] = $precision;
        }

        $table->addColumn($columnName, $type, $options);
    }

    /**
     * Parses the index definition and adds it to the schema table.
     *
     * @param Table  $table
     * @param string $keyName
     * @param string $sql
     */
    private function parseIndexSql(Table $table, $keyName, $sql)
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
        $options = [];

        foreach (explode(',', $matches[3]) as $column) {
            preg_match('/`([^`]+)`(\((\d+)\))?/', $column, $cm);

            $column = $cm[1];

            if (isset($cm[3])) {
                $column .= '('.$cm[3].')';
            }

            $columns[$cm[1]] = $column;
        }

        if (false !== strpos($matches[1], 'unique')) {
            $table->addUniqueIndex($columns, $matches[2], $options);
        } else {
            $table->addIndex($columns, $matches[2], [], $options);
        }
    }

    /**
     * Returns the SQL definitions from the Contao installer.
     *
     * @return array
     */
    private function getSqlDefinitions()
    {
        $framework = $this->container->get('contao.framework');
        $framework->initialize();

        $installer = $framework->createInstance('Contao\Database\Installer');

        $sql_target = $installer->getFromDca();
        $sql_legacy = $installer->getFromFile();

        // Manually merge the legacy definitions (see #4766)
        if (!empty($sql_legacy)) {
            foreach ($sql_legacy as $table => $categories) {
                foreach ($categories as $category => $fields) {
                    if (is_array($fields)) {
                        foreach ($fields as $name => $sql) {
                            $sql_target[$table][$category][$name] = $sql;
                        }
                    } else {
                        $sql_target[$table][$category] = $fields;
                    }
                }
            }
        }

        return $sql_target;
    }
}
