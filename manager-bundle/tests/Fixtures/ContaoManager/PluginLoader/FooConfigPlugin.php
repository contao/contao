<?php

namespace Foo\Config;

use Contao\ManagerBundle\ContaoManager\Config\ConfigPluginInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FooConfigPlugin implements ConfigPluginInterface
{
    public function prependConfig(array $configs, ContainerBuilder $container)
    {
    }
}
