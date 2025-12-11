<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\LoupeBridge\ContaoManager;

use Contao\ManagerPlugin\Config\ContainerBuilder as PluginContainerBuilder;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;

/**
 * @internal
 */
class Plugin implements ExtensionPluginInterface
{
    public function getExtensionConfig($extensionName, array $extensionConfigs, PluginContainerBuilder $container): array
    {
        switch ($extensionName) {
            case 'contao':
                return $this->addDefaultBackendSearchProvider($extensionConfigs);
        }

        return $extensionConfigs;
    }

    /**
     * Dynamically configures the back end search adapter if none was configured and
     * the system supports it.
     */
    private function addDefaultBackendSearchProvider(array $extensionConfigs): array
    {
        foreach ($extensionConfigs as $config) {
            // Back end search has been disabled
            if (false === ($config['backend_search'] ?? null) || false === ($config['backend_search']['enabled'] ?? null)) {
                return $extensionConfigs;
            }

            // Configured a custom adapter (e.g. MeiliSearch or whatever)
            if (isset($config['backend_search']['dsn'])) {
                return $extensionConfigs;
            }
        }

        $extensionConfigs[] = [
            'backend_search' => [
                'dsn' => 'loupe://%kernel.project_dir%/var/loupe',
            ],
        ];

        return $extensionConfigs;
    }
}
