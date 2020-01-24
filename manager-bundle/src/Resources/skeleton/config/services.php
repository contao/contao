<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * @var ContainerBuilder $container
 */
return static function(ContainerConfigurator $configurator) use ($container) {
    $originalDefinitions = $container->getDefinitions();

    // Don't do anything if there is a service definition for the App namespace
    foreach ($originalDefinitions as $id => $definition) {
        if (0 === strpos($id, 'App\\')) {
            return;
        }
    }

    $config = $configurator->services();
    $config->defaults()->autowire(true)->autoconfigure(true);

    $servicesDir = $container->getParameter('kernel.project_dir').'/src';

    try {
        $config
            ->load('App\\', $servicesDir.'/*')
            ->exclude($servicesDir.'/{DependencyInjection,Entity,Tests}')
        ;

        // Trigger __destruct handler
        unset($config);
    } catch (\Throwable $e) {
        // Ignore failed autoloading
    }

    $errors = [];
    $services = array_diff_key($container->getDefinitions(), $originalDefinitions);

    if (0 === ($serviceCount = \count($services))) {
        return;
    }

    foreach ($services as $id => $definition) {
        if ($definition->hasErrors()) {
            $errors[] = $id;
            continue;
        }

        $class = $definition->getClass() ?: $id;

        if (is_a($class, \Contao\System::class, true) || is_a($class, \Contao\Model::class, true)) {
            $container->removeDefinition($id);
            --$serviceCount;
            continue;
        }

        $ref = $container->getReflectionClass($class, false);

        if (null === $ref) {
            $errors[] = $id;
            continue;
        }

        // Class does not have a constructor, nothing to worry about
        if (!($constructor = $ref->getConstructor())) {
            continue;
        }

        if (!$constructor->isPublic()) {
            $errors[] = $id;
            continue;
        }

        foreach ($constructor->getParameters() as $index => $parameter) {
            if ($parameter->isDefaultValueAvailable() || $parameter->isOptional()) {
                continue;
            }

            $type = $parameter->getType();

            if ($type && !$type->isBuiltin() && (is_a($type->getName(), \Contao\System::class, true) || is_a($type->getName(), \Contao\Model::class, true))) {
                $container->removeDefinition($id);
                --$serviceCount;
                continue(2);
            }
        }
    }

    // If all services fail to register, there is probably another namespace in use
    if ($serviceCount === \count($errors)) {
        foreach ($errors as $id) {
            $container->removeDefinition($id);
        }
    }
};
