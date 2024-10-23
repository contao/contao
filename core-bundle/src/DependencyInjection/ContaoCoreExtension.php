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
use Contao\CoreBundle\DependencyInjection\Attribute\AsBlockInsertTag;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTagFlag;
use Contao\CoreBundle\DependencyInjection\Attribute\AsOperationForTemplateStudioElement;
use Contao\CoreBundle\DependencyInjection\Attribute\AsPage;
use Contao\CoreBundle\DependencyInjection\Attribute\AsPickerProvider;
use Contao\CoreBundle\DependencyInjection\Filesystem\ConfigureFilesystemInterface;
use Contao\CoreBundle\DependencyInjection\Filesystem\FilesystemConfiguration;
use Contao\CoreBundle\EventListener\SearchIndexListener;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Contao\CoreBundle\Migration\MigrationInterface;
use Contao\CoreBundle\Picker\PickerProviderInterface;
use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Search\Backend\Provider\ProviderInterface;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Imagine\Exception\RuntimeException as ImagineRuntimeException;
use Imagine\Gd\Imagine;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Toflar\CronjobSupervisor\Supervisor;

class ContaoCoreExtension extends Extension implements PrependExtensionInterface, ConfigureFilesystemInterface
{
    public function getAlias(): string
    {
        return 'contao';
    }

    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration();
    }

    public function prepend(ContainerBuilder $container): void
    {
        $configuration = new Configuration();

        $config = $container->getExtensionConfig($this->getAlias());
        $config = $container->getParameterBag()->resolveValue($config);
        $config = $this->processConfiguration($configuration, $config);

        // Prepend the backend route prefix to make it available for third-party
        // bundle configuration
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
            throw new RuntimeException(\sprintf('Using the charset "%s" is not supported, use "UTF-8" instead', $container->getParameter('kernel.charset')));
        }

        $projectDir = (string) $container->getParameter('kernel.project_dir');

        $configuration = new Configuration();
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
        $container->setParameter('contao.backend.crawl_concurrency', $config['backend']['crawl_concurrency']);
        $container->setParameter('contao.intl.locales', $config['intl']['locales']);
        $container->setParameter('contao.intl.enabled_locales', $config['intl']['enabled_locales']);
        $container->setParameter('contao.intl.countries', $config['intl']['countries']);
        $container->setParameter('contao.insert_tags.allowed_tags', $config['insert_tags']['allowed_tags']);
        $container->setParameter('contao.sanitizer.allowed_url_protocols', $config['sanitizer']['allowed_url_protocols']);

        $this->handleMessengerConfig($config, $container);
        $this->handleSearchConfig($config, $container);
        $this->handleBackendSearchConfig($config, $container, $loader);
        $this->handleCrawlConfig($config, $container);
        $this->setPredefinedImageSizes($config, $container);
        $this->setPreserveMetadataFields($config, $container);
        $this->setImagineService($config, $container);
        $this->handleTokenCheckerConfig($container);
        $this->handleBackup($config, $container);
        $this->handleFallbackPreviewProvider($config, $container);
        $this->handleCronConfig($config, $container);
        $this->handleSecurityConfig($config, $container);
        $this->handleCspConfig($config, $container);
        $this->handleAltcha($config, $container);
        $this->handTemplateStudioConfig($config, $container, $loader);

        $container
            ->registerForAutoconfiguration(PickerProviderInterface::class)
            ->addTag('contao.picker_provider')
        ;

        $container
            ->registerForAutoconfiguration(MigrationInterface::class)
            ->addTag('contao.migration')
        ;

        $container
            ->registerForAutoconfiguration(ContentUrlResolverInterface::class)
            ->addTag('contao.content_url_resolver')
        ;

        $container->registerAttributeForAutoconfiguration(
            AsContentElement::class,
            static function (ChildDefinition $definition, AsContentElement $attribute): void {
                $definition->addTag(ContentElementReference::TAG_NAME, $attribute->attributes);
            },
        );

        $container->registerAttributeForAutoconfiguration(
            AsFrontendModule::class,
            static function (ChildDefinition $definition, AsFrontendModule $attribute): void {
                $definition->addTag(FrontendModuleReference::TAG_NAME, $attribute->attributes);
            },
        );

        $attributesForAutoconfiguration = [
            AsPage::class => 'contao.page',
            AsPickerProvider::class => 'contao.picker_provider',
            AsCronJob::class => 'contao.cronjob',
            AsHook::class => 'contao.hook',
            AsCallback::class => 'contao.callback',
            AsInsertTag::class => 'contao.insert_tag',
            AsBlockInsertTag::class => 'contao.block_insert_tag',
            AsInsertTagFlag::class => 'contao.insert_tag_flag',
        ];

        foreach ($attributesForAutoconfiguration as $attributeClass => $tag) {
            $container->registerAttributeForAutoconfiguration(
                $attributeClass,
                static function (ChildDefinition $definition, object $attribute, \Reflector $reflector) use ($attributeClass, $tag): void {
                    $tagAttributes = get_object_vars($attribute);

                    if ($reflector instanceof \ReflectionMethod) {
                        if (isset($tagAttributes['method'])) {
                            throw new LogicException(\sprintf('%s attribute cannot declare a method on "%s::%s()".', $attributeClass, $reflector->getDeclaringClass()->getName(), $reflector->getName()));
                        }

                        $tagAttributes['method'] = $reflector->getName();
                    }

                    $definition->addTag($tag, $tagAttributes);
                },
            );
        }
    }

    public function configureFilesystem(FilesystemConfiguration $config): void
    {
        // User uploads
        $filesStorageName = 'files';

        // TODO: Deprecate the "contao.upload_path" config key. In the next major
        // version, $uploadPath can then be replaced with "files" and the redundant
        // "files" attribute removed when mounting the local adapter.
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

        // User templates
        $config
            ->mountLocalAdapter('templates', 'user_templates', 'user_templates')
            ->addVirtualFilesystem('user_templates', 'user_templates')
        ;
    }

    private function handleMessengerConfig(array $config, ContainerBuilder $container): void
    {
        if ($container->hasDefinition('contao.messenger.web_worker')) {
            $definition = $container->getDefinition('contao.messenger.web_worker');

            // Remove the entire service and all its listeners if there are no web worker
            // transports configured
            if ([] === $config['messenger']['web_worker']['transports']) {
                $container->removeDefinition('contao.messenger.web_worker');
            } else {
                $definition->setArgument(2, $config['messenger']['web_worker']['transports']);
                $definition->setArgument(3, $config['messenger']['web_worker']['grace_period']);
            }
        }

        if (
            !$container->hasDefinition('contao.cron.supervise_workers')
            || !$container->hasDefinition('contao.command.supervise_workers')
        ) {
            return;
        }

        // Disable workers completely if supervision is not supported
        if (!Supervisor::canSuperviseWithProviders(Supervisor::getDefaultProviders())) {
            $config['messenger']['workers'] = [];
        } else {
            $supervisor = new Definition(Supervisor::class);
            $supervisor->setFactory([Supervisor::class, 'withDefaultProviders']);
            $supervisor->addArgument('%kernel.cache_dir%/worker-supervisor');

            $command = $container->getDefinition('contao.command.supervise_workers');
            $command->setArgument(2, $supervisor);
            $command->setArgument(3, $config['messenger']['workers']);
        }

        // No workers defined -> remove our cron job and the command
        if (0 === \count($config['messenger']['workers'])) {
            $container->removeDefinition('contao.cron.supervise_workers');
            $container->removeDefinition('contao.command.supervise_workers');
        }
    }

    private function handleSearchConfig(array $config, ContainerBuilder $container): void
    {
        $container
            ->registerForAutoconfiguration(IndexerInterface::class)
            ->addTag('contao.search_indexer')
        ;

        // Set the two parameters, so they can be used in our legacy Config class for
        // maximum BC
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

    private function handleBackendSearchConfig(array $config, ContainerBuilder $container, LoaderInterface $loader): void
    {
        // Used to display/hide the search box in the back end
        $container->setParameter('contao.backend_search.enabled', $config['backend_search']['enabled']);

        if (!$config['backend_search']['enabled']) {
            return;
        }

        $container->registerForAutoconfiguration(ProviderInterface::class)->addTag('contao.backend_search_provider');
        $loader->load('backend_search.yaml');

        if (
            !$container->hasDefinition('contao.search_backend.adapter')
            || !$container->hasDefinition('contao.search_backend.engine')
        ) {
            return;
        }

        $indexName = $config['backend_search']['index_name'];

        $adapter = $container->getDefinition('contao.search_backend.adapter');
        $adapter->setArgument(0, $config['backend_search']['dsn']);

        $engine = $container->getDefinition('contao.search_backend.engine');
        $engine
            ->setArgument(1, (new Definition(BackendSearch::class))
                ->setFactory([null, 'getSearchEngineSchema'])
                ->setArgument(0, $indexName),
            )
        ;

        $factory = $container->getDefinition('contao.search.backend');
        $factory->setArgument(5, $indexName);
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
        $factory->setArgument(8, $config['crawl']['additional_uris']);
        $factory->setArgument(9, $config['crawl']['default_http_client_options']);
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

        // Do not add a size with the special name "_defaults" but merge its values into
        // all other definitions instead.
        foreach ($config['image']['sizes'] as $name => $value) {
            if ('_defaults' === $name) {
                continue;
            }

            if (isset($config['image']['sizes']['_defaults'])) {
                // Make sure that arrays defined under _defaults will take precedence over empty
                // arrays (see #2783)
                $value = [
                    ...$config['image']['sizes']['_defaults'],
                    ...array_filter($value, static fn ($v) => [] !== $v),
                ];
            }

            $imageSizes['_'.$name] = $this->camelizeKeys($value);

            // Do not camelize imagine options keys
            if ($value['imagine_options'] ?? false) {
                $imageSizes['_'.$name]['imagineOptions'] = $value['imagine_options'];
            }
        }

        $services = ['contao.image.sizes', 'contao.image.factory', 'contao.image.picture_factory', 'contao.image.preview_factory'];

        foreach ($services as $service) {
            if (method_exists((string) $container->getDefinition($service)->getClass(), 'setPredefinedSizes')) {
                $container->getDefinition($service)->addMethodCall('setPredefinedSizes', [$imageSizes]);
            }
        }
    }

    private function setPreserveMetadataFields(array $config, ContainerBuilder $container): void
    {
        if (!isset($config['image']['preserve_metadata_fields'])) {
            return;
        }

        $services = ['contao.image.factory', 'contao.image.picture_factory'];

        foreach ($services as $service) {
            if (method_exists((string) $container->getDefinition($service)->getClass(), 'setPreserveMetadataFields')) {
                $container->getDefinition($service)->addMethodCall('setPreserveMetadataFields', [$config['image']['preserve_metadata_fields']]);
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

    private function handleSecurityConfig(array $config, ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('contao.listener.transport_security_header')) {
            return;
        }

        if (false === $config['security']['hsts']['enabled']) {
            $container->removeDefinition('contao.listener.transport_security_header');

            return;
        }

        $listener = $container->getDefinition('contao.listener.transport_security_header');
        $listener->setArgument(1, $config['security']['hsts']['ttl']);
    }

    private function handleCspConfig(array $config, ContainerBuilder $container): void
    {
        if ($container->hasDefinition('contao.routing.response_context.csp_handler_factory')) {
            $factory = $container->getDefinition('contao.routing.response_context.csp_handler_factory');
            $factory->setArgument(1, $config['csp']['max_header_size']);
        }

        if ($container->hasDefinition('contao.csp.wysiwyg_style_processor')) {
            $processor = $container->getDefinition('contao.csp.wysiwyg_style_processor');
            $processor->setArgument(0, $config['csp']['allowed_inline_styles']);
        }
    }

    private function handleAltcha(array $config, ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('contao.altcha')) {
            return;
        }

        $altcha = $container->getDefinition('contao.altcha');
        $altcha->setArgument(3, $config['altcha']['algorithm']);
        $altcha->setArgument(4, $config['altcha']['range_max']);
        $altcha->setArgument(5, $config['altcha']['challenge_expiry']);
    }

    private function handTemplateStudioConfig(array $config, ContainerBuilder $container, LoaderInterface $loader): void
    {
        // Used to display/hide the menu entry in the back end
        $container->setParameter('contao.template_studio.enabled', $config['template_studio']['enabled']);

        if (!$config['template_studio']['enabled']) {
            return;
        }

        $this->registerOperationAttribute(AsOperationForTemplateStudioElement::class, 'contao.operation.template_studio_element', $container);

        $loader->load('template_studio.yaml');
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $attributeClass
     */
    private function registerOperationAttribute(string $attributeClass, string $tag, ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(
            $attributeClass,
            static function (ChildDefinition $definition, object $attribute, \Reflector $reflector) use ($tag): void {
                /** @var \ReflectionClass<T> $reflector */
                $tagAttributes = get_object_vars($attribute);

                $tagAttributes['name'] ??= (
                    static function () use ($reflector) {
                        // Derive name from class name - e.g. a "FooBarBazOperation" would become "foo_bar_baz"
                        preg_match('/([^\\\\]+)Operation$/', $reflector->getName(), $matches);

                        return Container::underscore($matches[1]);
                    }
                )();

                $definition->addTag($tag, $tagAttributes);

                if ($reflector->hasMethod('setName')) {
                    $definition->addMethodCall('setName', [$tagAttributes['name']]);
                }
            },
        );
    }
}
