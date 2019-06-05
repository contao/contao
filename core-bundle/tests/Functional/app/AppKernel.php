<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Functional\app;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\NewsBundle\ContaoNewsBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Knp\Bundle\MenuBundle\KnpMenuBundle;
use Knp\Bundle\TimeBundle\KnpTimeBundle;
use Psr\Log\NullLogger;
use Scheb\TwoFactorBundle\SchebTwoFactorBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
            new MonologBundle(),
            new SwiftmailerBundle(),
            new DoctrineBundle(),
            new SchebTwoFactorBundle(),
            new KnpTimeBundle(),
            new KnpMenuBundle(),
            new CmfRoutingBundle(),
            new ContaoCoreBundle(),
            new ContaoNewsBundle(),
        ];
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated since Symfony 4.2, use getProjectDir() instead
     */
    public function getRootDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return \dirname(__DIR__).'/var/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return \dirname(__DIR__).'/var/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/config/config_'.$this->environment.'.yml');
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->register('monolog.logger.contao', NullLogger::class);
    }
}
