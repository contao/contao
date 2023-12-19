<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\ContaoManager\Plugin;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\DelegatingParser;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Knp\Bundle\MenuBundle\KnpMenuBundle;
use Knp\Bundle\TimeBundle\KnpTimeBundle;
use Nelmio\CorsBundle\NelmioCorsBundle;
use Nelmio\SecurityBundle\NelmioSecurityBundle;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\SchebTwoFactorBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Terminal42\ServiceAnnotationBundle\Terminal42ServiceAnnotationBundle;

class PluginTest extends TestCase
{
    public function testReturnsTheBundles(): void
    {
        $plugin = new Plugin();

        /** @var array<BundleConfig> $bundles */
        $bundles = $plugin->getBundles(new DelegatingParser());

        $this->assertCount(6, $bundles);

        $this->assertSame(KnpMenuBundle::class, $bundles[0]->getName());
        $this->assertSame([], $bundles[0]->getReplace());
        $this->assertSame([], $bundles[0]->getLoadAfter());

        $this->assertSame(KnpTimeBundle::class, $bundles[1]->getName());
        $this->assertSame([], $bundles[1]->getReplace());
        $this->assertSame([], $bundles[1]->getLoadAfter());

        $this->assertSame(SchebTwoFactorBundle::class, $bundles[2]->getName());
        $this->assertSame([], $bundles[2]->getReplace());
        $this->assertSame([], $bundles[2]->getLoadAfter());

        $this->assertSame(CmfRoutingBundle::class, $bundles[3]->getName());
        $this->assertSame([], $bundles[3]->getReplace());
        $this->assertSame([], $bundles[3]->getLoadAfter());

        $this->assertSame(Terminal42ServiceAnnotationBundle::class, $bundles[4]->getName());
        $this->assertSame([], $bundles[4]->getReplace());
        $this->assertSame([], $bundles[4]->getLoadAfter());

        $this->assertSame(ContaoCoreBundle::class, $bundles[5]->getName());
        $this->assertSame(['core'], $bundles[5]->getReplace());

        $loadAfter = $bundles[5]->getLoadAfter();
        sort($loadAfter);

        $this->assertSame(
            [
                DoctrineBundle::class,
                KnpMenuBundle::class,
                KnpTimeBundle::class,
                NelmioCorsBundle::class,
                NelmioSecurityBundle::class,
                SchebTwoFactorBundle::class,
                FrameworkBundle::class,
                MonologBundle::class,
                SecurityBundle::class,
                TwigBundle::class,
                CmfRoutingBundle::class,
            ],
            $loadAfter,
        );
    }

    public function testReturnsTheRouteCollection(): void
    {
        $loader = $this->createMock(LoaderInterface::class);
        $loader
            ->expects($this->once())
            ->method('load')
        ;

        $resolver = $this->createMock(LoaderResolverInterface::class);
        $resolver
            ->method('resolve')
            ->willReturn($loader)
        ;

        $plugin = new Plugin();
        $plugin->getRouteCollection($resolver, $this->createMock(KernelInterface::class));
    }
}
