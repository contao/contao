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
use Doctrine\DBAL\Platforms\MySQLPlatform;

/**
 * Adds "saveTo" automatically to fields without an "sql" definition. Defines
 * "saveTo" targets automatically as "virtualTarget". Adds an "sql" definition
 * automatically to virtual field targets.
 */
#[AsHook('loadDataContainer', priority: -4096)]
class VirtualFieldsMappingListener
{
    public function __construct(private readonly string $defaultStorageName = 'jsonData')
    {
    }

    public function __invoke(string $table): void
    {
        if (!is_a(DataContainer::getDriverForTable($table), DC_Table::class, true)) {
            return;
        }

        $targets = [];

        $GLOBALS['TL_DCA'][$table]['fields'] = array_map(
            function (array $config) use (&$targets): array {
                // Automatically save to virtual field in DC_Table
                if (!\array_key_exists('sql', $config) && !\array_key_exists('saveTo', $config) && !\array_key_exists('input_field_callback', $config) && !\array_key_exists('save_callback', $config)) {
                    $config['saveTo'] = $this->defaultStorageName;
                }

                // Collect virtual field storage targets
                if ($config['saveTo'] ?? null) {
                    $targets[$config['saveTo']] = true;
                }

                return $config;
            },
            $GLOBALS['TL_DCA'][$table]['fields'],
        );

        // Configure virtual field targets
        foreach (array_keys($targets) as $target) {
            $GLOBALS['TL_DCA'][$table]['fields'][$target]['virtualTarget'] = true;

            if (!($GLOBALS['TL_DCA'][$table]['fields'][$target]['sql'] ?? null)) {
                $GLOBALS['TL_DCA'][$table]['fields'][$target]['sql'] = ['type' => 'json', 'length' => MySQLPlatform::LENGTH_LIMIT_MEDIUMTEXT, 'notnull' => false];
            }
        }
    }
}
