<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers the Contao session bags.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class AddSessionBagsPass implements CompilerPassInterface
{
    /**
     * @var array
     */
    private $mapper = [
        'contao_backend' => '_contao_be_attributes',
        'contao_frontend' => '_contao_fe_attributes',
    ];

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('session')) {
            return;
        }

        foreach ($this->mapper as $name => $storageKey) {
            $this->registerSessionBag($container, $storageKey, $name);
        }
    }

    /**
     * Registers a session bag.
     *
     * @param ContainerBuilder $container  The container object
     * @param string           $storageKey The storage key
     * @param string           $name       The session bag name
     */
    private function registerSessionBag(ContainerBuilder $container, $storageKey, $name)
    {
        $definition = new Definition('Contao\CoreBundle\Session\Attribute\ArrayAttributeBag', [$storageKey]);
        $definition->setPublic(false);
        $definition->addMethodCall('setName', [$name]);

        $id = 'contao.session_bag.' . $name;

        $container->setDefinition($id, $definition);
        $container->findDefinition('session')->addMethodCall('registerBag', [new Reference($id)]);
    }
}
