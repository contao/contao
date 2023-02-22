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

use Contao\CoreBundle\EventListener\GlobalsMapListener;
use Contao\CoreBundle\Fragment\FragmentConfig;
use Contao\CoreBundle\Fragment\FragmentOptionsAwareInterface;
use Contao\CoreBundle\Fragment\FragmentPreHandlerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers Contao fragments in the registry.
 *
 * For custom fragment tags, register your own compiler pass instance in your bundle.
 */
class RegisterFragmentsPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function __construct(
        private string|null $tag,
        private string|null $globalsKey = null,
        private string|null $proxyClass = null,
        private string|null $templateOptionsListener = null,
    ) {
    }

    /**
     * Adds the fragments to the registry.
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$this->tag || !$container->has('contao.fragment.registry')) {
            return;
        }

        $this->registerFragments($container, $this->tag);
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function registerFragments(ContainerBuilder $container, string $tag): void
    {
        $globals = [];
        $preHandlers = [];
        $templates = [];
        $registry = $container->findDefinition('contao.fragment.registry');
        $compositor = $container->findDefinition('contao.fragment.compositor');
        $command = $container->hasDefinition('contao.command.debug_fragments') ? $container->findDefinition('contao.command.debug_fragments') : null;

        foreach ($this->findAndSortTaggedServices($tag, $container) as $reference) {
            // If a controller has multiple methods for different fragment types (e.g. a content
            // element and a front end module), the first pass creates a child definition that
            // inherits all tags from the original. On the next run, the pass would pick up the
            // child definition and try to create duplicate fragments.
            if (str_starts_with((string) $reference, 'contao.fragment._')) {
                continue;
            }

            $definition = $container->findDefinition((string) $reference);
            $tags = $definition->getTag($tag);
            $definition->clearTag($tag);

            foreach ($tags as $attributes) {
                $attributes['type'] = $this->getFragmentType($definition, $attributes);
                $attributes['debugController'] = $this->getControllerName(new Reference($definition->getClass()), $attributes);

                $identifier = sprintf('%s.%s', $tag, $attributes['type']);
                $serviceId = 'contao.fragment._'.$identifier;

                $childDefinition = new ChildDefinition((string) $reference);
                $childDefinition->setPublic(true);

                $config = $this->getFragmentConfig($container, new Reference($serviceId), $attributes);

                $attributes['template'] ??= substr($tag, 7).'/'.$attributes['type'];
                $templates[$attributes['type']] = $attributes['template'];

                if (is_a($definition->getClass(), FragmentPreHandlerInterface::class, true)) {
                    $preHandlers[$identifier] = new Reference($serviceId);
                }

                if (is_a($definition->getClass(), FragmentOptionsAwareInterface::class, true)) {
                    $childDefinition->addMethodCall('setFragmentOptions', [$attributes]);
                }

                if (!$childDefinition->hasMethodCall('setContainer') && is_a($definition->getClass(), AbstractController::class, true)) {
                    $childDefinition->addMethodCall('setContainer', [new Reference(ContainerInterface::class)]);
                }

                $registry->addMethodCall('add', [$identifier, $config]);
                $command?->addMethodCall('add', [$identifier, $config, $attributes]);

                if ($attributes['slots'] ?? null) {
                    $compositor->addMethodCall('add', [$identifier, $attributes['slots']]);
                }

                $childDefinition->setTags($definition->getTags());
                $container->setDefinition($serviceId, $childDefinition);

                if ($this->globalsKey && $this->proxyClass) {
                    if (!isset($attributes['category'])) {
                        throw new InvalidConfigurationException(sprintf('Missing category for "%s" fragment on service ID "%s"', $tag, $reference));
                    }

                    $globals[$this->globalsKey][$attributes['category']][$attributes['type']] = $this->proxyClass;
                }
            }
        }

        $this->addPreHandlers($container, $preHandlers);
        $this->addGlobalsMapListener($globals, $container);

        if (null !== $this->templateOptionsListener && $container->hasDefinition($this->templateOptionsListener)) {
            $container->findDefinition($this->templateOptionsListener)->addMethodCall('setDefaultIdentifiersByType', [$templates]);
        }
    }

    protected function getFragmentConfig(ContainerBuilder $container, Reference $reference, array $attributes): Reference
    {
        $definition = new Definition(
            FragmentConfig::class,
            [
                $this->getControllerName($reference, $attributes),
                $attributes['renderer'] ?? 'forward',
                array_merge(['ignore_errors' => false], $attributes['options'] ?? []),
            ]
        );

        $serviceId = 'contao.fragment._config_'.ContainerBuilder::hash($definition);
        $container->setDefinition($serviceId, $definition);

        return new Reference($serviceId);
    }

    /**
     * Returns the controller name from the service and method name.
     */
    protected function getControllerName(Reference $reference, array $attributes): string
    {
        $controller = (string) $reference;

        // Support a specific method on the controller
        if (isset($attributes['method'])) {
            $controller .= ':'.$attributes['method'];
        }

        return $controller;
    }

    /**
     * @throws \RuntimeException
     */
    protected function addPreHandlers(ContainerBuilder $container, array $handlers): void
    {
        if (!$container->hasDefinition('contao.fragment.pre_handlers')) {
            throw new \RuntimeException('Missing service definition for "contao.fragment.pre_handlers"');
        }

        $definition = $container->getDefinition('contao.fragment.pre_handlers');
        $definition->setArgument(0, array_merge($definition->getArgument(0), $handlers));
    }

    protected function getFragmentType(Definition $definition, array $attributes): string
    {
        if (isset($attributes['type'])) {
            return (string) $attributes['type'];
        }

        $className = $definition->getClass();
        $className = ltrim(strrchr($className, '\\'), '\\');

        if (str_ends_with($className, 'Controller')) {
            $className = substr($className, 0, -10);
        }

        return Container::underscore($className);
    }

    private function addGlobalsMapListener(array $globals, ContainerBuilder $container): void
    {
        if (empty($globals)) {
            return;
        }

        $listener = new Definition(GlobalsMapListener::class, [$globals]);
        $listener->setPublic(true);
        $listener->addTag('contao.hook', ['hook' => 'initializeSystem', 'priority' => 255]);

        $container->setDefinition('contao.listener.'.ContainerBuilder::hash($listener), $listener);
    }
}
