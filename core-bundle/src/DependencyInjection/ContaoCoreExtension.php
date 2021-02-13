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

use Contao\CoreBundle\Crawl\Escargot\Subscriber\EscargotSubscriberInterface;
use Contao\CoreBundle\EventListener\SearchIndexListener;
use Contao\CoreBundle\Migration\MigrationInterface;
use Contao\CoreBundle\Picker\PickerProviderInterface;
use Contao\CoreBundle\Routing\Page\ContentCompositionInterface;
use Contao\CoreBundle\Routing\Page\DynamicRouteInterface;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Imagine\Exception\RuntimeException;
use Imagine\Gd\Imagine;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Webmozart\PathUtil\Path;

class ContaoCoreExtension extends Extension
{
    public function getAlias(): string
    {
        return 'contao';
    }

    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration(
            $container->getParameter('kernel.project_dir'),
            $container->getParameter('kernel.default_locale')
        );
    }

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

        $loader->load('commands.yml');
        $loader->load('listener.yml');
        $loader->load('services.yml');
        $loader->load('migrations.yml');

        $container->setParameter('contao.web_dir', $config['web_dir']);
        $container->setParameter('contao.encryption_key', $config['encryption_key']);
        $container->setParameter('contao.upload_path', $config['upload_path']);
        $container->setParameter('contao.editable_files', $config['editable_files']);
        $container->setParameter('contao.preview_script', $config['preview_script']);
        $container->setParameter('contao.csrf_cookie_prefix', $config['csrf_cookie_prefix']);
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
        $container->setParameter('contao.localconfig', $config['localconfig'] ?? []);
        $container->setParameter('contao.backend', $config['backend']);
        $container->setParameter('contao.backend.route_prefix', $config['backend']['route_prefix']);

        $this->handleSearchConfig($config, $container);
        $this->handleCrawlConfig($config, $container);
        $this->setPredefinedImageSizes($config, $container);
        $this->setImagineService($config, $container);
        $this->overwriteImageTargetDir($config, $container);
        $this->handleTokenCheckerConfig($config, $container);
        $this->handleLegacyRouting($config, $configs, $container, $loader);

        $container
            ->registerForAutoconfiguration(PickerProviderInterface::class)
            ->addTag('contao.picker_provider')
        ;

        $container
            ->registerForAutoconfiguration(MigrationInterface::class)
            ->addTag('contao.migration')
        ;

        $container
            ->registerForAutoconfiguration(DynamicRouteInterface::class)
            ->addTag('contao.page')
        ;

        $container
            ->registerForAutoconfiguration(ContentCompositionInterface::class)
            ->addTag('contao.page')
        ;
    }

    private function handleSearchConfig(array $config, ContainerBuilder $container): void
    {
        $container
            ->registerForAutoconfiguration(IndexerInterface::class)
            ->addTag('contao.search_indexer')
        ;

        // Set the two parameters so they can be used in our legacy Config class for maximum BC
        $container->setParameter('contao.search.default_indexer.enable', $config['search']['default_indexer']['enable']);
        $container->setParameter('contao.search.index_protected', $config['search']['index_protected']);

        if (!$config['search']['default_indexer']['enable']) {
            // Remove the default indexer completely if it was disabled
            $container->removeDefinition('contao.search.indexer.default');
        } else {
            // Configure whether to index protected pages on the default indexer
            $defaultIndexer = $container->getDefinition('contao.search.indexer.default');
            $defaultIndexer->setArgument(2, $config['search']['index_protected']);
        }

        $features = SearchIndexListener::FEATURE_INDEX | SearchIndexListener::FEATURE_DELETE;

        if (!$config['search']['listener']['index']) {
            $features ^= SearchIndexListener::FEATURE_INDEX;
        }

        if (!$config['search']['listener']['delete']) {
            $features ^= SearchIndexListener::FEATURE_DELETE;
        }

        if (0 === $features) {
            // Remove the search index listener if no features are enabled
            $container->removeDefinition('contao.listener.search_index');
        } else {
            // Configure the search index listener
            $container->getDefinition('contao.listener.search_index')->setArgument(2, $features);
        }
    }

    private function handleCrawlConfig(array $config, ContainerBuilder $container): void
    {
        $container
            ->registerForAutoconfiguration(EscargotSubscriberInterface::class)
            ->addTag('contao.escargot_subscriber')
        ;

        if (!$container->hasDefinition('contao.crawl.escargot_factory')) {
            return;
        }

        $factory = $container->getDefinition('contao.crawl.escargot_factory');
        $factory->setArgument(2, $config['crawl']['additional_uris']);
        $factory->setArgument(3, $config['crawl']['default_http_client_options']);
    }

    /**
     * Validates and sets the "contao.image.sizes" parameter.
     */
    private function setPredefinedImageSizes(array $config, ContainerBuilder $container): void
    {
        if (!isset($config['image']['sizes']) || 0 === \count($config['image']['sizes'])) {
            return;
        }

        $imageSizes = [];

        // Do not add a size with the special name '_defaults' but merge its values into all other definitions instead.
        foreach ($config['image']['sizes'] as $name => $value) {
            if ('_defaults' === $name) {
                continue;
            }

            if (isset($config['image']['sizes']['_defaults'])) {
                $value = array_merge($config['image']['sizes']['_defaults'], $value);
            }

            $imageSizes['_'.$name] = $this->camelizeKeys($value);
        }

        $services = ['contao.image.image_sizes', 'contao.image.image_factory', 'contao.image.picture_factory'];

        foreach ($services as $service) {
            if (method_exists((string) $container->getDefinition($service)->getClass(), 'setPredefinedSizes')) {
                $container->getDefinition($service)->addMethodCall('setPredefinedSizes', [$imageSizes]);
            }
        }
    }

    /**
     * Camelizes keys so "resize_mode" becomes "resizeMode".
     */
    private function camelizeKeys(array $config): array
    {
        $keys = array_keys($config);

        foreach ($keys as &$key) {
            if (\is_array($config[$key])) {
                $config[$key] = $this->camelizeKeys($config[$key]);
            }

            if (\is_string($key)) {
                $key = lcfirst(Container::camelize($key));
            }
        }

        unset($key);

        return array_combine($keys, $config);
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
            Path::join($container->getParameter('kernel.project_dir'), $config['image']['target_path'])
        );

        trigger_deprecation('contao/core-bundle', '4.4', 'Using the "contao.image.target_path" parameter has been deprecated and will no longer work in Contao 5.0. Use the "contao.image.target_dir" parameter instead.');
    }

    private function handleTokenCheckerConfig(array $config, ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('contao.security.token_checker')) {
            return;
        }

        $tokenChecker = $container->getDefinition('contao.security.token_checker');
        $tokenChecker->replaceArgument(5, new Reference('security.access.simple_role_voter'));

        if ($container->hasParameter('security.role_hierarchy.roles') && \count($container->getParameter('security.role_hierarchy.roles')) > 0) {
            $tokenChecker->replaceArgument(5, new Reference('security.access.role_hierarchy_voter'));
        }
    }

    private function handleLegacyRouting(array $mergedConfig, array $configs, ContainerBuilder $container, YamlFileLoader $loader): void
    {
        if (false === $mergedConfig['legacy_routing']) {
            foreach ($configs as $config) {
                if (isset($config['prepend_locale'])) {
                    throw new InvalidConfigurationException('Setting contao.prepend_locale to "'.var_export($config['prepend_locale'], true).'" requires legacy routing.');
                }

                if (isset($config['url_suffix'])) {
                    throw new InvalidConfigurationException('Setting contao.url_suffix to "'.$config['url_suffix'].'" requires legacy routing.');
                }
            }
        }

        $container->setParameter('contao.legacy_routing', $mergedConfig['legacy_routing']);
        $container->setParameter('contao.prepend_locale', $mergedConfig['prepend_locale']);
        $container->setParameter('contao.url_suffix', $mergedConfig['url_suffix']);

        if ($mergedConfig['legacy_routing']) {
            $loader->load('legacy_routing.yml');
        }
    }
}
