<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\WebhooksBundle\Tests\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\WebhooksBundle\ContaoManager\Plugin;
use Contao\WebhooksBundle\ContaoWebhooksBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Loader\AttributeFileLoader;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;

final class PluginTest extends TestCase
{
    public function testRegistersTheWebhookBundleAfterContaoCore(): void
    {
        $bundles = new Plugin()->getBundles($this->createStub(ParserInterface::class));

        $this->assertCount(1, $bundles);
        $this->assertSame(ContaoWebhooksBundle::class, $bundles[0]->getName());
        $this->assertSame([ContaoCoreBundle::class], $bundles[0]->getLoadAfter());
    }

    public function testLoadsWebhookRoutes(): void
    {
        $path = \dirname(__DIR__, 2).'/config/routes.yaml';
        $routes = new RouteCollection();

        $loader = $this->createMock(LoaderInterface::class);
        $loader
            ->expects($this->once())
            ->method('load')
            ->with($path)
            ->willReturn($routes)
        ;

        $resolver = $this->createMock(LoaderResolverInterface::class);
        $resolver
            ->expects($this->once())
            ->method('resolve')
            ->with($path)
            ->willReturn($loader)
        ;

        $this->assertSame($routes, new Plugin()->getRouteCollection($resolver, $this->createStub(KernelInterface::class)));
    }

    public function testLoadsTheIncomingWebhookRoute(): void
    {
        $locator = new FileLocator();
        $attributeLoader = new AttributeFileLoader($locator, new AttributeRouteControllerLoader());
        $yamlLoader = new YamlFileLoader($locator);
        $resolver = new LoaderResolver([$yamlLoader, $attributeLoader]);

        $yamlLoader->setResolver($resolver);
        $attributeLoader->setResolver($resolver);

        $routes = $yamlLoader->load(\dirname(__DIR__, 2).'/config/routes.yaml');

        $this->assertNotNull($routes->get('contao_webhook_incoming'));
    }
}
