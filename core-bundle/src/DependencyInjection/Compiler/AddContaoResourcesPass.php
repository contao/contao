<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds Contao resources and public folders to the contao.resource_provider service.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class AddContaoResourcesPass implements CompilerPassInterface
{
    private $bundleName;
    private $resourcesPath;
    private $publicFolders;

    /**
     * Constructor.
     *
     * @param string $resourcesPath
     * @param array  $publicFolders
     */
    public function __construct($resourcesPath, array $publicFolders = [])
    {
        $this->resourcesPath = $resourcesPath;
        $this->publicFolders = $publicFolders;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('contao.resource_provider')) {
            return;
        }

        $definition = $container->findDefinition('contao.resource_provider');

        $definition->addMethodCall('addResourcesPath', [$this->resourcesPath]);

        if (!empty($this->publicFolders)) {
            $definition->addMethodCall('addPublicFolders', [$this->publicFolders]);
        }
    }
}
