<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Contao\CoreBundle\Doctrine\DBAL\Types\BinaryStringType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class DoctrineTypesPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter('doctrine.dbal.connection_factory.types')) {
            return;
        }

        $types = $container->getParameter('doctrine.dbal.connection_factory.types');

        $types[BinaryStringType::NAME] = [
            'class' => BinaryStringType::class,
            'commented' => true,
        ];

        $container->setParameter('doctrine.dbal.connection_factory.types', $types);
        $container->getDefinition('doctrine.dbal.connection_factory')->replaceArgument(0, $types);
    }
}
