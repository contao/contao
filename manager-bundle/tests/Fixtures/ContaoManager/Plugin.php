<?php

namespace Contao\ManagerBundle\Tests\Fixtures\ContaoManager;

use Contao\ManagerPlugin\Config\ContainerBuilder;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;

class Plugin implements ExtensionPluginInterface
{
    public function getExtensionConfig($extensionName, array $extensionConfigs, ContainerBuilder $container)
    {
    }
}
