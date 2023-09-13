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

use Contao\CoreBundle\Routing\Page\ContentCompositionInterface;
use Contao\CoreBundle\Routing\Page\DynamicRouteInterface;
use Contao\CoreBundle\Routing\Page\RouteConfig;
use Contao\FrontendIndex;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Routing\Route;

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
        if (!$container->has('contao.routing.page_registry')) {
            return;
        }

        $this->registerPages($container);
    }

    protected function registerPages(ContainerBuilder $container): void
    {
        $registry = $container->findDefinition('contao.routing.page_registry');
        $command = $container->hasDefinition('contao.command.debug_pages') ? $container->findDefinition('contao.command.debug_pages') : null;

        foreach ($this->findAndSortTaggedServices(self::TAG_NAME, $container) as $reference) {
            $definition = $container->findDefinition((string) $reference);
            $tags = $definition->getTag(self::TAG_NAME);

            $definition->clearTag(self::TAG_NAME);

            if (!$definition->hasMethodCall('setContainer') && is_a($definition->getClass(), AbstractController::class, true)) {
                $definition->addMethodCall('setContainer', [new Reference(ContainerInterface::class)]);
            }

            foreach ($tags as $attributes) {
                $routeEnhancer = null;
                $contentComposition = (bool) ($attributes['contentComposition'] ?? true);
                $class = $definition->getClass();
                $type = $this->getPageType($class, $attributes);

                if (is_a($class, DynamicRouteInterface::class, true)) {
                    $routeEnhancer = $reference;
                }

                if (is_a($class, ContentCompositionInterface::class, true)) {
                    $contentComposition = $reference;
                }

                $config = $this->getRouteConfig($reference, $definition, $attributes);
                $registry->addMethodCall('add', [$type, $config, $routeEnhancer, $contentComposition]);
                $command?->addMethodCall('add', [$type, $config, $routeEnhancer, $contentComposition]);
            }
        }
    }

    protected function getRouteConfig(Reference $reference, Definition $definition, array $attributes): Definition
    {
        $defaults = $attributes['defaults'] ?? [];
        $defaults['_controller'] = $this->getControllerName($reference, $definition, $attributes);

        $path = $attributes['path'] ?? null;
        $pathRegex = null;

        if (\is_string($path) && str_starts_with($path, '/')) {
            $compiledRoute = (new Route($path, $defaults, $attributes['requirements'] ?? [], $attributes['options'] ?? []))->compile();
            $pathRegex = $compiledRoute->getRegex();
        }

        return new Definition(RouteConfig::class, [
            $path,
            $pathRegex,
            $attributes['urlSuffix'] ?? null,
            $attributes['requirements'] ?? [],
            $attributes['options'] ?? [],
            $defaults,
            $attributes['methods'] ?? [],
        ]);
    }

    /**
     * Returns the controller name from the service and method name.
     */
    private function getControllerName(Reference $reference, Definition $definition, array $attributes): string
    {
        if (isset($attributes['defaults']['_controller'])) {
            return $attributes['defaults']['_controller'];
        }

        $controller = (string) $reference;

        // Support a specific method on the controller
        if (isset($attributes['method'])) {
            $definition->setPublic(true);

            return $controller.':'.$attributes['method'];
        }

        if (($class = $definition->getClass()) && method_exists($class, '__invoke')) {
            $definition->setPublic(true);

            return $controller;
        }

        return FrontendIndex::class.'::renderPage';
    }

    private function getPageType(string $className, array $attributes): string
    {
        if (isset($attributes['type'])) {
            return (string) $attributes['type'];
        }

        $className = ltrim(strrchr($className, '\\'), '\\');

        if (str_ends_with($className, 'Controller')) {
            $className = substr($className, 0, -10);
        }

        if (str_ends_with($className, 'Page')) {
            $className = substr($className, 0, -4);
        }

        return Container::underscore($className);
    }
}
