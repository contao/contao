<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\DataContainer;
use Contao\DC_Table;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Adds "targetColumn" automatically to fields without an "sql" definition.
 * Defines "targetColumn" fields automatically as "virtualTarget". Adds an "sql"
 * definition automatically to virtual field targets.
 */
#[AsHook('loadDataContainer', priority: -4096)]
class VirtualFieldsMappingListener
{
    public function __construct(
        private readonly EntityManagerInterface|null $entityManager = null,
        private readonly string $defaultStorageName = 'jsonData',
    ) {
    }

    public function __invoke(string $table): void
    {
        // Ignore DCAs whose tables are defined via a Doctrine entity
        if ($this->entityManager) {
            $entityTables = array_map(
                static fn (ClassMetadata $metadata) => $metadata->getTableName(),
                $this->entityManager->getMetadataFactory()->getAllMetadata(),
            );

            if (\in_array($table, $entityTables, true)) {
                return;
            }
        }

        // Only support auto-mapping for DC_Table
        if (!is_a(DataContainer::getDriverForTable($table), DC_Table::class, true)) {
            return;
        }

        // Check if the schema is managed by Contao
        if (!($GLOBALS['TL_DCA'][$table]['config']['sql'] ?? null)) {
            return;
        }

        // Ignore any DCAs that are not editable or do not have any palettes
        if (($GLOBALS['TL_DCA'][$table]['config']['notEditable'] ?? null) || !($GLOBALS['TL_DCA'][$table]['palettes'] ?? null)) {
            return;
        }

        $GLOBALS['TL_DCA'][$table]['fields'] = array_map(
            function (array $config): array {
                // Automatically save to virtual field in DC_Table
                if (!\array_key_exists('sql', $config) && !\array_key_exists('targetColumn', $config) && !\array_key_exists('input_field_callback', $config) && !\array_key_exists('save_callback', $config)) {
                    $config['targetColumn'] = $this->defaultStorageName;
                }

                return $config;
            },
            $GLOBALS['TL_DCA'][$table]['fields'] ?? [],
        );

        // Configure virtual field targets
        foreach (array_unique(array_column($GLOBALS['TL_DCA'][$table]['fields'], 'targetColumn')) as $target) {
            $GLOBALS['TL_DCA'][$table]['fields'][$target]['virtualTarget'] = true;

            if (!($GLOBALS['TL_DCA'][$table]['fields'][$target]['sql'] ?? null)) {
                $GLOBALS['TL_DCA'][$table]['fields'][$target]['sql'] = ['type' => 'json', 'length' => AbstractMySQLPlatform::LENGTH_LIMIT_MEDIUMTEXT, 'notnull' => false];
            }
        }
    }
}
