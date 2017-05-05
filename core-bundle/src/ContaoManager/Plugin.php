<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Knp\Bundle\MenuBundle\KnpMenuBundle;
use Knp\Bundle\TimeBundle\KnpTimeBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Plugin for the Contao Manager.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(KnpMenuBundle::class),
            BundleConfig::create(KnpTimeBundle::class),
            BundleConfig::create(ContaoCoreBundle::class)
                ->setReplace(['core'])
                ->setLoadAfter(
                    [
                        'Symfony\Bundle\FrameworkBundle\FrameworkBundle',
                        'Symfony\Bundle\SecurityBundle\SecurityBundle',
                        'Symfony\Bundle\TwigBundle\TwigBundle',
                        'Symfony\Bundle\MonologBundle\MonologBundle',
                        'Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle',
                        'Doctrine\Bundle\DoctrineBundle\DoctrineBundle',
                        'Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle',
                        'Knp\Bundle\TimeBundle\KnpTimeBundle',
                        'Lexik\Bundle\MaintenanceBundle\LexikMaintenanceBundle',
                        'Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle',
                        'Contao\ManagerBundle\ContaoManagerBundle',
                    ]
                ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {
        return $resolver
            ->resolve(__DIR__.'/../Resources/config/routing.yml')
            ->load(__DIR__.'/../Resources/config/routing.yml')
        ;
    }
}
