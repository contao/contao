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
            if (\is_a($definition->getClass(), \Contao\System::class)) {
                $container->removeDefinition($id);
                --$serviceCount;
                continue;
            }

            $errors[] = $id;
        }
    }

    // If all services fail to register, there is probably another namespace in use
    if ($serviceCount === \count($errors)) {
        foreach ($errors as $id) {
            $container->removeDefinition($id);
        }
    }
};
