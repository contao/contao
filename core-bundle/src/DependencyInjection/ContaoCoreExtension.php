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
use Contao\CoreBundle\Cron\CronJob;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\DependencyInjection\Attribute\AsPage;
use Contao\CoreBundle\DependencyInjection\Attribute\AsPickerProvider;
use Contao\CoreBundle\DependencyInjection\Filesystem\ConfigureFilesystemInterface;
use Contao\CoreBundle\DependencyInjection\Filesystem\FilesystemConfiguration;
use Contao\CoreBundle\EventListener\SearchIndexListener;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Contao\CoreBundle\Migration\MigrationInterface;
use Contao\CoreBundle\Picker\PickerProviderInterface;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Imagine\Exception\RuntimeException as ImagineRuntimeException;
use Imagine\Gd\Imagine;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class ContaoCoreExtension extends Extension implements PrependExtensionInterface, ConfigureFilesystemInterface
{
    public function getAlias(): string
    {
        return 'contao';
    }

    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration((string) $container->getParameter('kernel.project_dir'));
    }

    public function prepend(ContainerBuilder $container): void
    {
        $configuration = new Configuration((string) $container->getParameter('kernel.project_dir'));

        $config = $container->getExtensionConfig($this->getAlias());
        $config = $container->getParameterBag()->resolveValue($config);
        $config = $this->processConfiguration($configuration, $config);

        // Prepend the backend route prefix to make it available for third-party bundle configuration
        $container->setParameter('contao.backend.route_prefix', $config['backend']['route_prefix']);

        // Make sure channels for all Contao log actions are available
        if ($container->hasExtension('monolog')) {
            $container->prependExtensionConfig('monolog', [
                'channels' => [
                    'contao.access',
                    'contao.configuration',
                    'contao.cron',
                    'contao.email',
                    'contao.error',
                    'contao.files',
                    'contao.forms',
                    'contao.general',
                ],
            ]);
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        if ('UTF-8' !== $container->getParameter('kernel.charset')) {
            throw new RuntimeException(sprintf('Using the charset "%s" is not supported, use "UTF-8" instead', $container->getParameter('kernel.charset')));
        }

        $projectDir = (string) $container->getParameter('kernel.project_dir');

        $configuration = new Configuration($projectDir);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('commands.yaml');
        $loader->load('controller.yaml');
        $loader->load('listener.yaml');
        $loader->load('migrations.yaml');
        $loader->load('services.yaml');

        $container->setParameter('contao.web_dir', $this->getComposerPublicDir($projectDir) ?? Path::join($projectDir, 'public'));
        $container->setParameter('contao.console_path', $config['console_path']);
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
        $container->setParameter('contao.image.preview.target_dir', $config['image']['preview']['target_dir']);
        $container->setParameter('contao.image.preview.default_size', $config['image']['preview']['default_size']);
        $container->setParameter('contao.image.preview.max_size', $config['image']['preview']['max_size']);
        $container->setParameter('contao.security.two_factor.enforce_backend', $config['security']['two_factor']['enforce_backend']);
        $container->setParameter('contao.localconfig', $config['localconfig'] ?? []);
        $container->setParameter('contao.backend.attributes', $config['backend']['attributes']);
        $container->setParameter('contao.backend.custom_css', $config['backend']['custom_css']);
        $container->setParameter('contao.backend.custom_js', $config['backend']['custom_js']);
        $container->setParameter('contao.backend.badge_title', $config['backend']['badge_title']);
        $container->setParameter('contao.backend.route_prefix', $config['backend']['route_prefix']);
        $container->setParameter('contao.intl.locales', $config['intl']['locales']);
        $container->setParameter('contao.intl.enabled_locales', $config['intl']['enabled_locales']);
        $container->setParameter('contao.intl.countries', $config['intl']['countries']);
        $container->setParameter('contao.insert_tags.allowed_tags', $config['insert_tags']['allowed_tags']);
        $container->setParameter('contao.sanitizer.allowed_url_protocols', $config['sanitizer']['allowed_url_protocols']);

        $this->handleMessengerConfig($config, $container);
        $this->handleSearchConfig($config, $container);
        $this->handleCrawlConfig($config, $container);
        $this->setPredefinedImageSizes($config, $container);
        $this->setPreserveMetadata($config, $container);
        $this->setImagineService($config, $container);
        $this->handleTokenCheckerConfig($container);
        $this->handleBackup($config, $container);
        $this->handleFallbackPreviewProvider($config, $container);
        $this->handleCronConfig($config, $container);

        $container
            ->registerForAutoconfiguration(PickerProviderInterface::class)
            ->addTag('contao.picker_provider')
        ;

        $container
            ->registerForAutoconfiguration(MigrationInterface::class)
            ->addTag('contao.migration')
        ;

        $container->registerAttributeForAutoconfiguration(
            AsContentElement::class,
            static function (ChildDefinition $definition, AsContentElement $attribute): void {
                $definition->addTag(ContentElementReference::TAG_NAME, $attribute->attributes);
            }
        );

        $container->registerAttributeForAutoconfiguration(
            AsFrontendModule::class,
            static function (ChildDefinition $definition, AsFrontendModule $attribute): void {
                $definition->addTag(FrontendModuleReference::TAG_NAME, $attribute->attributes);
            }
        );

        $attributesForAutoconfiguration = [
            AsPage::class => 'contao.page',
            AsPickerProvider::class => 'contao.picker_provider',
            AsCronJob::class => 'contao.cronjob',
            AsHook::class => 'contao.hook',
            AsCallback::class => 'contao.callback',
        ];

        foreach ($attributesForAutoconfiguration as $attributeClass => $tag) {
            $container->registerAttributeForAutoconfiguration(
                $attributeClass,
                static function (ChildDefinition $definition, object $attribute, \Reflector $reflector) use ($attributeClass, $tag): void {
                    $tagAttributes = get_object_vars($attribute);

                    if ($reflector instanceof \ReflectionMethod) {
                        if (isset($tagAttributes['method'])) {
                            throw new LogicException(sprintf('%s attribute cannot declare a method on "%s::%s()".', $attributeClass, $reflector->getDeclaringClass()->getName(), $reflector->getName()));
                        }

                        $tagAttributes['method'] = $reflector->getName();
                    }

                    $definition->addTag($tag, $tagAttributes);
                }
            );
        }

        if ($container->hasParameter('kernel.debug') && $container->getParameter('kernel.debug')) {
            $loader->load('services_debug.yaml');
        }
    }

    public function configureFilesystem(FilesystemConfiguration $config): void
    {
        // User uploads
        $filesStorageName = 'files';

        // TODO: Deprecate the "contao.upload_path" config key. In the next
        // major version, $uploadPath can then be replaced with "files" and the
        // redundant "files" attribute removed when mounting the local adapter.
        $uploadPath = $config->getContainer()->getParameterBag()->resolveValue('%contao.upload_path%');

        $config
            ->mountLocalAdapter($uploadPath, $uploadPath, 'files')
            ->addVirtualFilesystem($filesStorageName, $uploadPath)
        ;

        $config
            ->addDefaultDbafs($filesStorageName, 'tl_files')
            ->addMethodCall('setDatabasePathPrefix', [$uploadPath]) // Backwards compatibility
        ;

        // Backups
        $config
            ->mountLocalAdapter('var/backups', 'backups', 'backups')
            ->addVirtualFilesystem('backups', 'backups')
        ;
    }

    private function handleMessengerConfig(array $config, ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('contao.cron.messenger')) {
            return;
        }

        // No workers defined -> remove our cron job
        if (0 === \count($config['messenger']['workers'])) {
            $container->removeDefinition('contao.cron.messenger');

            return;
        }

        $cron = $container->getDefinition('contao.cron.messenger');
        $cron->setArgument(2, $config['messenger']['workers']);
    }

    private function handleSearchConfig(array $config, ContainerBuilder $container): void
    {
        $container
            ->registerForAutoconfiguration(IndexerInterface::class)
            ->addTag('contao.search_indexer')
        ;

        // Set the two parameters, so they can be used in our legacy Config class for maximum BC
        $container->setParameter('contao.search.default_indexer.enable', $config['search']['default_indexer']['enable']);
        $container->setParameter('contao.search.index_protected', $config['search']['index_protected']);

        if (!$config['search']['default_indexer']['enable']) {
            // Remove the default indexer completely if it was disabled
            $container->removeDefinition('contao.search.default_indexer');
        } else {
            // Configure whether to index protected pages on the default indexer
            $defaultIndexer = $container->getDefinition('contao.search.default_indexer');
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

        if (!$container->hasDefinition('contao.crawl.escargot.factory')) {
            return;
        }

        $factory = $container->getDefinition('contao.crawl.escargot.factory');
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

        // Do not add a size with the special name "_defaults" but merge its values into all other definitions instead.
        foreach ($config['image']['sizes'] as $name => $value) {
            if ('_defaults' === $name) {
                continue;
            }

            if (isset($config['image']['sizes']['_defaults'])) {
                // Make sure that arrays defined under _defaults will take precedence over empty arrays (see #2783)
                $value = [
                    ...$config['image']['sizes']['_defaults'],
                    ...array_filter($value, static fn ($v) => !\is_array($v) || !empty($v)),
                ];
            }

            $imageSizes['_'.$name] = $this->camelizeKeys($value);
        }

        $services = ['contao.image.sizes', 'contao.image.factory', 'contao.image.picture_factory', 'contao.image.preview_factory'];

        foreach ($services as $service) {
            if (method_exists((string) $container->getDefinition($service)->getClass(), 'setPredefinedSizes')) {
                $container->getDefinition($service)->addMethodCall('setPredefinedSizes', [$imageSizes]);
            }
        }
    }

    private function setPreserveMetadata(array $config, ContainerBuilder $container): void
    {
        if (!isset($config['image']['preserve_metadata'])) {
            return;
        }

        $services = ['contao.image.factory', 'contao.image.picture_factory'];

        foreach ($services as $service) {
            if (method_exists((string) $container->getDefinition($service)->getClass(), 'setPreserveMetadata')) {
                $container->getDefinition($service)->addMethodCall('setPreserveMetadata', [$config['image']['preserve_metadata']]);
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
            } catch (ImagineRuntimeException) {
                continue;
            }

            return $class;
        }

        return Imagine::class; // see #616
    }

    private function handleTokenCheckerConfig(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('contao.security.token_checker')) {
            return;
        }

        $tokenChecker = $container->getDefinition('contao.security.token_checker');

        if ($container->hasParameter('security.role_hierarchy.roles') && \count($container->getParameter('security.role_hierarchy.roles')) > 0) {
            $tokenChecker->replaceArgument(4, new Reference('security.access.role_hierarchy_voter'));
        } else {
            $tokenChecker->replaceArgument(4, new Reference('security.access.simple_role_voter'));
        }
    }

    private function handleBackup(array $config, ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('contao.doctrine.backup_manager')) {
            return;
        }

        if (!$container->hasDefinition('contao.doctrine.backup.retention_policy')) {
            return;
        }

        $retentionPolicy = $container->getDefinition('contao.doctrine.backup.retention_policy');
        $retentionPolicy->setArgument(0, $config['backup']['keep_max']);
        $retentionPolicy->setArgument(1, $config['backup']['keep_intervals']);

        $dbDumper = $container->getDefinition('contao.doctrine.backup_manager');
        $dbDumper->setArgument(3, $config['backup']['ignore_tables']);
    }

    private function handleFallbackPreviewProvider(array $config, ContainerBuilder $container): void
    {
        if (
            $config['image']['preview']['enable_fallback_images']
            || !$container->hasDefinition('contao.image.fallback_preview_provider')
        ) {
            return;
        }

        $container->removeDefinition('contao.image.fallback_preview_provider');
    }

    private function handleCronConfig(array $config, ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('contao.listener.command_scheduler') || !$container->hasDefinition('contao.cron')) {
            return;
        }

        if (false === $config['cron']['web_listener']) {
            $container->removeDefinition('contao.listener.command_scheduler');

            return;
        }

        $scheduler = $container->getDefinition('contao.listener.command_scheduler');
        $scheduler->setArgument(3, false);

        if ('auto' === $config['cron']['web_listener']) {
            $scheduler->setArgument(3, true);

            $container->getDefinition('contao.cron')->addMethodCall('addCronJob', [
                new Definition(CronJob::class, [new Reference('contao.cron'), '* * * * *', 'updateMinutelyCliCron']),
            ]);
        }
    }

    private function getComposerPublicDir(string $projectDir): string|null
    {
        $fs = new Filesystem();

        if (!$fs->exists($composerJsonFilePath = Path::join($projectDir, 'composer.json'))) {
            return null;
        }

        $composerConfig = json_decode(file_get_contents($composerJsonFilePath), true, 512, JSON_THROW_ON_ERROR);

        if (null === ($publicDir = $composerConfig['extra']['public-dir'] ?? null)) {
            return null;
        }

        return Path::join($projectDir, $publicDir);
    }
}
