<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Contao\CoreBundle\EventListener\DataContainer\ContentCompositionListener;
use Contao\CoreBundle\Routing\Page\CompositionAwareInterface;
use Contao\CoreBundle\Routing\Page\PageRouteProviderInterface;
use Contao\CoreBundle\Routing\Page\RouteConfig;
use Contao\CoreBundle\Routing\Page\UrlSuffixProviderInterface;
use Contao\FrontendIndex;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Registers Contao pages in the registry.
 */
class RegisterPagesPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    private const TAG_NAME = 'contao.page';

    /**
     * Adds the fragments to the registry.
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('contao.routing.page_route_factory') || !$container->has('contao.routing.candidates')) {
            return;
        }

        $this->registerPages($container);
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function registerPages(ContainerBuilder $container): void
    {
        $factory = $container->findDefinition('contao.routing.page_route_factory');
        $candidates = $container->findDefinition('contao.routing.candidates');

        $compositionAware = [];

        foreach ($this->findAndSortTaggedServices(self::TAG_NAME, $container) as $reference) {
            $definition = $container->findDefinition($reference);
            $definition->setPublic(true);

            $tags = $definition->getTag(self::TAG_NAME);
            $definition->clearTag(self::TAG_NAME);

            foreach ($tags as $attributes) {
                $type = $this->getPageType($definition, $attributes);
                unset($attributes['type']);

                $routeProvider = null;
                $class = $definition->getClass();

                if (is_a($definition->getClass(), PageRouteProviderInterface::class, true)) {
                    $routeProvider = $reference;
                }

                if (is_a($class, UrlSuffixProviderInterface::class, true)) {
                    $candidates->addMethodCall('addUrlSuffixProvider', [$reference]);
                }

                if (is_a($class, CompositionAwareInterface::class, true)) {
                    $compositionAware[] = $reference;
                }

                $config = $this->getRouteConfig($reference, $class, $attributes);
                $factory->addMethodCall('add', [$type, $config, $routeProvider]);

                $definition->addTag(self::TAG_NAME, $attributes);
            }
        }

        $container
            ->findDefinition(ContentCompositionListener::class)
            ->replaceArgument(
                2,
                new Definition(
                    ServiceLocator::class,
                    $compositionAware
                )
            )
        ;
    }

    protected function getRouteConfig(Reference $reference, string $class, array $attributes): Definition
    {
        $defaults = $attributes['defaults'] ?? [];
        $defaults['_controller'] = $this->getControllerName($reference, $class, $attributes);

        return new Definition(
            RouteConfig::class,
            [
                $attributes['requirements'] ?? [],
                $attributes['options'] ?? [],
                $defaults,
                $attributes['methods'] ?? [],
            ]
        );
    }

    /**
     * Returns the controller name from the service and method name.
     */
    private function getControllerName(Reference $reference, string $class, array $attributes): string
    {
        if (isset($attributes['defaults']['_controller'])) {
            return $attributes['defaults']['_controller'];
        }

        $controller = (string) $reference;

        // Support a specific method on the controller
        if (isset($attributes['method'])) {
            return $controller.':'.$attributes['method'];
        }

        if (method_exists($class, '__invoke')) {
            return $controller;
        }

        return FrontendIndex::class.'::renderPage';
    }

    private function getPageType(Definition $definition, array $attributes): string
    {
        if (isset($attributes['type'])) {
            return (string) $attributes['type'];
        }

        $className = $definition->getClass();
        $className = ltrim(strrchr($className, '\\'), '\\');

        if ('Controller' === substr($className, -10)) {
            $className = substr($className, 0, -10);
        }

        return Container::underscore($className);
    }
}
