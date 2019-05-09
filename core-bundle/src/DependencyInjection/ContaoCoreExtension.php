<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DependencyInjection;

use Contao\CoreBundle\Picker\PickerProviderInterface;
use Imagine\Exception\RuntimeException;
use Imagine\Gd\Imagine;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class ContaoCoreExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function getAlias(): string
    {
        return 'contao';
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration(
             $container->getParameter('kernel.project_dir'),
             $container->getParameter('kernel.default_locale')
         );
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration(
            $container->getParameter('kernel.project_dir'),
            $container->getParameter('kernel.default_locale')
        );

        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        static $files = [
            'commands.yml',
            'listener.yml',
            'services.yml',
        ];

        foreach ($files as $file) {
            $loader->load($file);
        }

        $container->setParameter('contao.web_dir', $config['web_dir']);
        $container->setParameter('contao.prepend_locale', $config['prepend_locale']);
        $container->setParameter('contao.encryption_key', $config['encryption_key']);
        $container->setParameter('contao.url_suffix', $config['url_suffix']);
        $container->setParameter('contao.upload_path', $config['upload_path']);
        $container->setParameter('contao.csrf_token_name', $config['csrf_token_name']);
        $container->setParameter('contao.pretty_error_screens', $config['pretty_error_screens']);
        $container->setParameter('contao.error_level', $config['error_level']);
        $container->setParameter('contao.locales', $config['locales']);
        $container->setParameter('contao.image.bypass_cache', $config['image']['bypass_cache']);
        $container->setParameter('contao.image.target_dir', $config['image']['target_dir']);
        $container->setParameter('contao.image.valid_extensions', $config['image']['valid_extensions']);
        $container->setParameter('contao.image.imagine_options', $config['image']['imagine_options']);
        $container->setParameter('contao.image.reject_large_uploads', $config['image']['reject_large_uploads']);
        $container->setParameter('contao.security.two_factor.enforce_backend', $config['security']['two_factor']['enforce_backend']);

        if (isset($config['localconfig'])) {
            $container->setParameter('contao.localconfig', $config['localconfig']);
        }

        $this->setImagineService($config, $container);
        $this->overwriteImageTargetDir($config, $container);

        $container
            ->registerForAutoconfiguration(PickerProviderInterface::class)
            ->addTag('contao.picker_provider')
        ;
    }

    /**
     * Configures the "contao.image.imagine" service.
     */
    private function setImagineService(array $config, ContainerBuilder $container): void
    {
        $imagineServiceId = $config['image']['imagine_service'];

        // Generate if not present
        if (null === $imagineServiceId) {
            $class = $this->getImagineImplementation();
            $imagineServiceId = 'contao.image.imagine.'.ContainerBuilder::hash($class);

            $container->setDefinition($imagineServiceId, new Definition($class));
        }

        $container->setAlias('contao.image.imagine', $imagineServiceId)->setPublic(true);
    }

    private function getImagineImplementation(): string
    {
        static $magicks = ['Gmagick', 'Imagick'];

        foreach ($magicks as $name) {
            $class = 'Imagine\\'.$name.'\Imagine';

            // Will throw an exception if the PHP implementation is not available
            try {
                new $class();
            } catch (RuntimeException $e) {
                continue;
            }

            return $class;
        }

        return Imagine::class; // see #616
    }

    /**
     * Reads the old contao.image.target_path parameter.
     */
    private function overwriteImageTargetDir(array $config, ContainerBuilder $container): void
    {
        if (!isset($config['image']['target_path'])) {
            return;
        }

        $container->setParameter(
            'contao.image.target_dir',
            $container->getParameter('kernel.project_dir').'/'.$config['image']['target_path']
        );

        @trigger_error('Using the contao.image.target_path parameter has been deprecated and will no longer work in Contao 5.0. Use the contao.image.target_dir parameter instead.', E_USER_DEPRECATED);
    }
}
