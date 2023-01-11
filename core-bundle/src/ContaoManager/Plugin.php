<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Knp\Bundle\MenuBundle\KnpMenuBundle;
use Knp\Bundle\TimeBundle\KnpTimeBundle;
use Nelmio\CorsBundle\NelmioCorsBundle;
use Nelmio\SecurityBundle\NelmioSecurityBundle;
use Scheb\TwoFactorBundle\SchebTwoFactorBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;
use Terminal42\ServiceAnnotationBundle\Terminal42ServiceAnnotationBundle;

/**
 * @internal
 */
class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(KnpMenuBundle::class),
            BundleConfig::create(KnpTimeBundle::class),
            BundleConfig::create(SchebTwoFactorBundle::class),
            BundleConfig::create(CmfRoutingBundle::class),
            BundleConfig::create(Terminal42ServiceAnnotationBundle::class),
            BundleConfig::create(ContaoCoreBundle::class)
                ->setReplace(['core'])
                ->setLoadAfter(
                    [
                        FrameworkBundle::class,
                        SecurityBundle::class,
                        TwigBundle::class,
                        MonologBundle::class,
                        DoctrineBundle::class,
                        KnpMenuBundle::class,
                        KnpTimeBundle::class,
                        NelmioCorsBundle::class,
                        NelmioSecurityBundle::class,
                        SchebTwoFactorBundle::class,
                        CmfRoutingBundle::class,
                    ]
                ),
        ];
    }

    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): RouteCollection|null
    {
        return $resolver
            ->resolve(__DIR__.'/../../config/routes.yaml')
            ->load(__DIR__.'/../../config/routes.yaml')
        ;
    }
}
