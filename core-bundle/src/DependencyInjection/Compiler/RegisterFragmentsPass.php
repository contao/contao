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

use Contao\CoreBundle\Fragment\Annotation\Base;
use Contao\CoreBundle\Fragment\Annotation\ContentElement;
use Contao\CoreBundle\Fragment\Annotation\FrontendModule;
use Contao\CoreBundle\Fragment\FragmentConfig;
use Contao\CoreBundle\Fragment\FragmentOptionsAwareInterface;
use Contao\CoreBundle\Fragment\FragmentPreHandlerInterface;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Doctrine\Common\Annotations\CachedReader;
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

        $this->registerTaggedServices($container, ContentElementReference::TAG_NAME);
        $this->registerTaggedServices($container, FrontendModuleReference::TAG_NAME);

        // Load annotated controllers after tagged services, because annotated controllers get a tag which would then be parsed again
        $this->registerAnnotatedControllers($container, ContentElement::class, ContentElementReference::TAG_NAME);
        $this->registerAnnotatedControllers($container, FrontendModule::class, FrontendModuleReference::TAG_NAME);
    }

    private function registerAnnotatedControllers(ContainerBuilder $container, string $annotationName, string $tag): void
    {
        if (count($dirs = $this->getAnnotatedControllersDirs($container)) === 0) {
            return;
        }

        /** @var CachedReader $annotationReader */
        $annotationReader = $container->get('annotations.cached_reader');

        foreach ($dirs as $dir => $namespace) {
            $container->addResource(new DirectoryResource($dir, '/\.php$/'));

            $finder = Finder::create()->files()->in($dir)->name('/\.php$/');

            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                $currentNamespace = $namespace;

                // Include subfolders in namespace
                if (($subdirs = $file->getRelativePath()) !== '') {
                    $currentNamespace .= '\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $subdirs);
                }

                $class = $currentNamespace . '\\' . \pathinfo($file->getFilename(), PATHINFO_FILENAME);

                if (!class_exists($class)) {
                    continue;
                }

                $reflection = new \ReflectionClass($class);

                // Class annotations
                /** @var $annotation Base */
                if (($annotation = $annotationReader->getClassAnnotation($reflection, $annotationName)) !== null) {
                    $this->registerAnnotationFragment($container, $annotation, $tag, $class, '__invoke');
                }

                // Method annotations
                foreach ($reflection->getMethods() as $method) {
                    /** @var $annotation Base */
                    if (($annotation = $annotationReader->getMethodAnnotation($method, $annotationName)) !== null) {
                        $this->registerAnnotationFragment($container, $annotation, $tag, $class, $method->getName());
                    }
                }
            }
        }
    }

    private function registerAnnotationFragment(ContainerBuilder $container, Base $annotation, string $tag, string $class, string $method): void
    {
        $serviceId = $annotation->service ?: $class;

        if (!$container->hasDefinition($serviceId)) {
            $container->setDefinition($serviceId, new Definition($class));
        }

        $this->registerFragment($container, new Reference($serviceId), $tag, [
            'category' => $annotation->category,
            'method' => $method,
            'options' => $annotation->options,
            'template' => $annotation->template,
            'renderer' => $annotation->renderer,
            'type' => $annotation->type,
        ]);
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

    private function registerTaggedServices(ContainerBuilder $container, string $tag): void
    {
        foreach ($this->findAndSortTaggedServices($tag, $container) as $priority => $reference) {
            $definition = $container->findDefinition($reference);

            foreach ($definition->getTag($tag) as $attributes) {
                // Clear the tag before registering a fragment
                $definition = $definition->clearTag($tag);
                $this->registerFragment($container, $reference, $tag, $attributes);
            }
        }
    }

    private function registerFragment(ContainerBuilder $container, Reference $reference, string $tag, array $attributes): void
    {
        $registry = $container->findDefinition('contao.fragment.registry');

        $definition = $container->findDefinition($reference);
        $definition->setPublic(true);

        $attributes['type'] = $this->getFragmentType($definition, $attributes);
        $identifier = sprintf('%s.%s', $tag, $attributes['type']);
        $config = $this->getFragmentConfig($container, $reference, $attributes);

        if (is_a($definition->getClass(), FragmentPreHandlerInterface::class, true)) {
            $this->addPreHandlers($container, [[$identifier => $reference]]);
        }

        if (is_a($definition->getClass(), FragmentOptionsAwareInterface::class, true)) {
            $definition->addMethodCall('setFragmentOptions', [$attributes]);
        }

        $registry->addMethodCall('add', [$identifier, $config]);

        // Remove all the arrays from tag attribute as they are not supported
        $definition->addTag($tag, array_filter($attributes, function ($v) {
            return is_scalar($v);
        }));
    }

    /**
     * @deprecated Deprecated since Contao 4.8, to be removed in Contao 5.0
     */
    protected function registerFragments(ContainerBuilder $container, string $tag): void
    {
        @trigger_error('Using the RegisterFragmentsPass::registerFragments() has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

        $this->registerTaggedServices($container, $tag);
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
