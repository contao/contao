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

use Contao\CoreBundle\Fragment\Annotation\ContentElement;
use Contao\CoreBundle\Fragment\Annotation\FrontendModule;
use Contao\CoreBundle\Fragment\FragmentConfig;
use Contao\CoreBundle\Fragment\FragmentOptionsAwareInterface;
use Contao\CoreBundle\Fragment\FragmentPreHandlerInterface;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Registers Contao fragments in the registry.
 *
 * For custom fragment tags, create your own compiler pass by extending this
 * class and replacing the process() method.
 */
class RegisterFragmentsPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * Adds the fragments to the registry.
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('contao.fragment.registry')) {
            return;
        }

        $this->registerAnnotatedControllers($container);
        $this->registerFragments($container, ContentElementReference::TAG_NAME);
        $this->registerFragments($container, FrontendModuleReference::TAG_NAME);
    }

    private function registerAnnotatedControllers(ContainerBuilder $container): void
    {
        if (count($dirs = $this->getAnnotatedControllersDirs($container)) === 0) {
            return;
        }

        $preHandlers = [];
        $registry = $container->findDefinition('contao.fragment.registry');
        $annotationReader = $container->get('annotations.cached_reader');

        foreach ($dirs as $dir => $namespace) {
            $container->addResource(new DirectoryResource($dir, '/\.php$/'));

            $finder = Finder::create()->files()->in($dir)->name('/\.php$/');

            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                $class = $namespace . '\\' . \pathinfo($file->getFilename(), PATHINFO_FILENAME);

                if (!class_exists($class)) {
                    continue;
                }

                $reflection = new \ReflectionClass($class);

                foreach ($annotationReader->getClassAnnotations($reflection) as $annotation) {
                    if (!$annotation instanceof ContentElement || !$annotation instanceof FrontendModule) {
                        continue;
                    }

                    $serviceId = $annotation->service ?: $class;

                    if ($container->hasDefinition($serviceId)) {
                        $definition = $container->getDefinition($serviceId);
                    } else {
                        $definition = new Definition($class);
                        $container->setDefinition($serviceId, $definition);
                    }

                    $definition->setPublic(true);

                    $attributes = [
                        'category' => $annotation->category,
                        'method' => '__invoke', // TODO
                        //'options' => $annotation->options, // TODO
                        'renderer' => $annotation->renderer,
                        'template' => $annotation->template,
                        'type' => $annotation->type,
                    ];

                    $attributes['type'] = $this->getFragmentType($definition, $attributes);

                    $tag = ($annotation instanceof ContentElement) ? ContentElementReference::TAG_NAME : FrontendModuleReference::TAG_NAME;

                    $identifier = sprintf('%s.%s', $tag, $attributes['type']);
                    $reference = new Reference($serviceId);
                    $config = $this->getFragmentConfig($container, $reference, $attributes);

                    if (is_a($definition->getClass(), FragmentPreHandlerInterface::class, true)) {
                        $preHandlers[$identifier] = $reference;
                    }

                    if (is_a($definition->getClass(), FragmentOptionsAwareInterface::class, true)) {
                        $definition->addMethodCall('setFragmentOptions', [$attributes]);
                    }

                    $registry->addMethodCall('add', [$identifier, $config]);
                }
            }
        }

        $this->addPreHandlers($container, $preHandlers);
    }

    private function getAnnotatedControllersDirs(ContainerBuilder $container)
    {
        $dirs = [];

        foreach ($container->getParameter('kernel.bundles') as $name => $class) {
            $bundle = new \ReflectionClass($class);
            $dirs[\dirname($bundle->getFileName())] = $bundle->getNamespaceName() . '\\Controller';
        }

        $dirs[$container->getParameter('kernel.project_dir') . '/src/Controller'] = 'App\\Controller';

        return $dirs;
    }

    /**
     * @throws InvalidConfigurationException
     */
    protected function registerFragments(ContainerBuilder $container, string $tag): void
    {
        $preHandlers = [];
        $registry = $container->findDefinition('contao.fragment.registry');

        foreach ($this->findAndSortTaggedServices($tag, $container) as $priority => $reference) {
            $definition = $container->findDefinition($reference);
            $definition->setPublic(true);

            $tags = $definition->getTag($tag);
            $definition->clearTag($tag);

            foreach ($tags as $attributes) {
                $attributes['type'] = $this->getFragmentType($definition, $attributes);

                $identifier = sprintf('%s.%s', $tag, $attributes['type']);
                $config = $this->getFragmentConfig($container, $reference, $attributes);

                if (is_a($definition->getClass(), FragmentPreHandlerInterface::class, true)) {
                    $preHandlers[$identifier] = $reference;
                }

                if (is_a($definition->getClass(), FragmentOptionsAwareInterface::class, true)) {
                    $definition->addMethodCall('setFragmentOptions', [$attributes]);
                }

                $registry->addMethodCall('add', [$identifier, $config]);
                $definition->addTag($tag, $attributes);
            }
        }

        $this->addPreHandlers($container, $preHandlers);
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

        if ('Controller' === substr($className, -10)) {
            $className = substr($className, 0, -10);
        }

        return Container::underscore($className);
    }
}
