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

use Contao\Config;
use Contao\CoreBundle\Doctrine\Backup\RetentionPolicy;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\Image\Metadata\ExifFormat;
use Contao\Image\Metadata\IptcFormat;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;
use Imagine\Image\ImageInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Filesystem\Path;

class Configuration implements ConfigurationInterface
{
    public function __construct(private readonly string $projectDir)
    {
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('contao');
        $treeBuilder
            ->getRootNode()
            ->children()
                ->scalarNode('csrf_cookie_prefix')
                    ->cannotBeEmpty()
                    ->defaultValue('csrf_')
                ->end()
                ->scalarNode('csrf_token_name')
                    ->cannotBeEmpty()
                    ->defaultValue('contao_csrf_token')
                ->end()
                ->integerNode('error_level')
                    ->info('The error reporting level set when the framework is initialized. Defaults to E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED.')
                    ->min(-1)
                    ->max(32767)
                    ->defaultValue(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED)
                ->end()
                ->append($this->addIntlNode())
                ->variableNode('localconfig')
                    ->info('Allows to set TL_CONFIG variables, overriding settings stored in localconfig.php. Changes in the Contao back end will not have any effect.')
                    ->validate()
                        ->always(
                            static function (array $options): array {
                                foreach (array_keys($options) as $option) {
                                    if ($newKey = Config::getNewKey($option)) {
                                        trigger_deprecation('contao/core-bundle', '5.0', 'Setting "contao.localconfig.%s" has been deprecated. Use "%s" instead.', $option, $newKey);
                                    }
                                }

                                return $options;
                            },
                        )
                    ->end()
                ->end()
                ->arrayNode('locales')
                    ->info('Allows to configure which languages can be used in the Contao back end. Defaults to all languages for which a translation exists.')
                    ->setDeprecated('contao/core-bundle', '4.12', 'Using contao.locales is deprecated. Please use contao.intl.enabled_locales instead.')
                    ->prototype('scalar')->end()
                    ->defaultValue([])
                ->end()
                ->booleanNode('pretty_error_screens')
                    ->info('Show customizable, pretty error screens instead of the default PHP error messages.')
                    ->defaultValue(false)
                ->end()
                ->scalarNode('preview_script')
                    ->info('An optional entry point script that bypasses the front end cache for previewing changes (e.g. "/preview.php").')
                    ->cannotBeEmpty()
                    ->defaultValue('')
                    ->validate()
                        ->always(static fn (string $value): string => Path::canonicalize($value))
                    ->end()
                ->end()
                ->scalarNode('upload_path')
                    ->info('The folder used by the file manager.')
                    ->cannotBeEmpty()
                    ->defaultValue('files')
                    ->validate()
                        ->ifTrue(static fn (string $v): int => preg_match('@^(app|assets|bin|config|contao|plugins|public|share|system|templates|var|vendor|web)(/|$)@', $v))
                        ->thenInvalid('%s')
                    ->end()
                ->end()
                ->scalarNode('editable_files')
                    ->defaultValue('css,csv,html,ini,js,json,less,md,scss,svg,svgz,ts,txt,xliff,xml,yml,yaml')
                ->end()
                ->scalarNode('web_dir')
                    ->info('Absolute path to the web directory. Defaults to %kernel.project_dir%/public.')
                    ->setDeprecated('contao/core-bundle', '4.13', 'Setting the web directory in a config file is deprecated. Use the "extra.public-dir" config key in your root composer.json instead.')
                    ->cannotBeEmpty()
                    ->defaultValue('public')
                    ->validate()
                        ->always(static fn (string $value): string => Path::canonicalize($value))
                    ->end()
                ->end()
                ->scalarNode('console_path')
                    ->info('The path to the Symfony console. Defaults to %kernel.project_dir%/bin/console.')
                    ->cannotBeEmpty()
                    ->defaultValue('%kernel.project_dir%/bin/console')
                ->end()
                ->append($this->addMessengerNode())
                ->append($this->addImageNode())
                ->append($this->addSecurityNode())
                ->append($this->addSearchNode())
                ->append($this->addCrawlNode())
                ->append($this->addMailerNode())
                ->append($this->addBackendNode())
                ->append($this->addInsertTagsNode())
                ->append($this->addBackupNode())
                ->append($this->addSanitizerNode())
                ->append($this->addCronNode())
            ->end()
        ;

        return $treeBuilder;
    }

    private function addMessengerNode(): NodeDefinition
    {
        return (new TreeBuilder('messenger'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->info('Allows to define Symfony Messenger workers (messenger:consume). Workers are started every minute using the Contao cron job framework.')
            ->children()
                ->arrayNode('workers')
                    ->performNoDeepMerging()
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('transports')
                                ->info('The transports/receivers you would like to consume from.')
                                ->example(['foobar_transport', 'foobar2_transport'])
                                ->scalarPrototype()
                                ->end()
                            ->end()
                            ->arrayNode('options')
                                ->info('messenger:consume options. Make sure to always include "--time-limit=60".')
                                ->example(['--sleep=5', '--time-limit=60'])
                                ->scalarPrototype()->end()
                                ->defaultValue(['--time-limit=60'])
                                ->validate()
                                    ->ifTrue(static fn (array $options) => !\in_array('--time-limit=60', $options, true))
                                    ->thenInvalid('Custom messenger:consume options must include "--time-limit=60".')
                                ->end()
                            ->end()
                            ->arrayNode('autoscale')
                                ->info('Enables autoscaling.')
                                ->canBeEnabled()
                                ->children()
                                    ->integerNode('desired_size')
                                        ->info('Contao will automatically autoscale the number of workers to meet this queue size. Logic: desiredWorkers = ceil(currentSize / desiredSize)')
                                        ->isRequired()
                                        ->min(1)
                                    ->end()
                                    ->integerNode('min')
                                        ->min(1)
                                        ->defaultValue(1)
                                        ->info('Contao will never scale down to less than this configured number of workers.')
                                    ->end()
                                    ->integerNode('max')
                                        ->isRequired()
                                        ->min(1)
                                        ->info('Contao will never scale up to more than this configured number of workers.')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addImageNode(): NodeDefinition
    {
        return (new TreeBuilder('image'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('bypass_cache')
                    ->info('Bypass the image cache and always regenerate images when requested. This also disables deferred image resizing.')
                    ->defaultValue(false)
                ->end()
                ->append($this->addImagineOptionsNode(true))
                ->scalarNode('imagine_service')
                    ->info('Contao automatically uses an Imagine service out of Gmagick, Imagick and Gd (in this order). Set a service ID here to override.')
                    ->defaultNull()
                ->end()
                ->booleanNode('reject_large_uploads')
                    ->info('Reject uploaded images exceeding the localconfig.imageWidth and localconfig.imageHeight dimensions.')
                    ->defaultValue(false)
                ->end()
                ->arrayNode('sizes')
                    ->info('Allows to define image sizes in the configuration file in addition to in the Contao back end. Use the special name "_defaults" to preset values for all sizes of the configuration file.')
                    ->useAttributeAsKey('name')
                    ->validate()
                        ->always(
                            static function (array $value): array {
                                static $reservedImageSizeNames = [
                                    ResizeConfiguration::MODE_BOX,
                                    ResizeConfiguration::MODE_PROPORTIONAL,
                                    ResizeConfiguration::MODE_CROP,
                                    'left_top',
                                    'center_top',
                                    'right_top',
                                    'left_center',
                                    'center_center',
                                    'right_center',
                                    'left_bottom',
                                    'center_bottom',
                                    'right_bottom',
                                ];

                                foreach (array_keys($value) as $name) {
                                    if (preg_match('/^\d+$/', (string) $name)) {
                                        throw new \InvalidArgumentException(sprintf('The image size name "%s" cannot contain only digits', $name));
                                    }

                                    if (\in_array($name, $reservedImageSizeNames, true)) {
                                        throw new \InvalidArgumentException(sprintf('"%s" is a reserved image size name (reserved names: %s)', $name, implode(', ', $reservedImageSizeNames)));
                                    }

                                    if (preg_match('/[^a-z0-9_]/', (string) $name)) {
                                        throw new \InvalidArgumentException(sprintf('The image size name "%s" must consist of lowercase letters, digits and underscores only', $name));
                                    }
                                }

                                return $value;
                            },
                        )
                    ->end()
                    ->arrayPrototype()
                        ->children()
                            ->integerNode('width')
                            ->end()
                            ->integerNode('height')
                            ->end()
                            ->enumNode('resize_mode')
                                ->values([
                                    ResizeConfiguration::MODE_CROP,
                                    ResizeConfiguration::MODE_BOX,
                                    ResizeConfiguration::MODE_PROPORTIONAL,
                                ])
                            ->end()
                            ->integerNode('zoom')
                                ->min(0)
                                ->max(100)
                            ->end()
                            ->scalarNode('css_class')
                            ->end()
                            ->booleanNode('lazy_loading')
                            ->end()
                            ->scalarNode('densities')
                            ->end()
                            ->scalarNode('sizes')
                            ->end()
                            ->booleanNode('skip_if_dimensions_match')
                                ->info('If the output dimensions match the source dimensions, the image will not be processed. Instead, the original file will be used.')
                            ->end()
                            ->arrayNode('formats')
                                ->info('Allows to convert one image format to another or to provide additional image formats for an image (e.g. WebP).')
                                ->example(['jpg' => ['jxl', 'webp', 'jpg'], 'gif' => ['avif', 'png']])
                                ->useAttributeAsKey('source')
                                ->arrayPrototype()
                                    ->beforeNormalization()->castToArray()->end()
                                    ->scalarPrototype()->end()
                                ->end()
                            ->end()
                            ->arrayNode('preserve_metadata_fields')
                                ->info('Which metadata fields to preserve when resizing images.')
                                ->example([ExifFormat::NAME => ExifFormat::DEFAULT_PRESERVE_KEYS, IptcFormat::NAME => IptcFormat::DEFAULT_PRESERVE_KEYS])
                                ->useAttributeAsKey('format')
                                ->arrayPrototype()
                                    ->beforeNormalization()->castToArray()->end()
                                    ->scalarPrototype()->end()
                                ->end()
                            ->end()
                            ->append($this->addImagineOptionsNode(false))
                            ->arrayNode('items')
                                ->arrayPrototype()
                                    ->children()
                                        ->integerNode('width')
                                        ->end()
                                        ->integerNode('height')
                                        ->end()
                                        ->enumNode('resize_mode')
                                            ->values([
                                                ResizeConfiguration::MODE_CROP,
                                                ResizeConfiguration::MODE_BOX,
                                                ResizeConfiguration::MODE_PROPORTIONAL,
                                            ])
                                        ->end()
                                        ->integerNode('zoom')
                                            ->min(0)
                                            ->max(100)
                                        ->end()
                                        ->scalarNode('media')
                                        ->end()
                                        ->scalarNode('densities')
                                        ->end()
                                        ->scalarNode('sizes')
                                        ->end()
                                        ->enumNode('resizeMode')
                                            ->setDeprecated('contao/core-bundle', '4.9', 'Using contao.image.sizes.*.items.resizeMode is deprecated. Please use contao.image.sizes.*.items.resize_mode instead.')
                                            ->values([
                                                ResizeConfiguration::MODE_CROP,
                                                ResizeConfiguration::MODE_BOX,
                                                ResizeConfiguration::MODE_PROPORTIONAL,
                                            ])
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->enumNode('resizeMode')
                                ->setDeprecated('contao/core-bundle', '4.9', 'Using contao.image.sizes.*.resizeMode is deprecated. Please use contao.image.sizes.*.resize_mode instead.')
                                ->values([
                                    ResizeConfiguration::MODE_CROP,
                                    ResizeConfiguration::MODE_BOX,
                                    ResizeConfiguration::MODE_PROPORTIONAL,
                                ])
                            ->end()
                            ->scalarNode('cssClass')
                                ->setDeprecated('contao/core-bundle', '4.9', 'Using contao.image.sizes.*.cssClass is deprecated. Please use contao.image.sizes.*.css_class instead.')
                            ->end()
                            ->booleanNode('lazyLoading')
                                ->setDeprecated('contao/core-bundle', '4.9', 'Using contao.image.sizes.*.lazyLoading is deprecated. Please use contao.image.sizes.*.lazy_loading instead.')
                            ->end()
                            ->booleanNode('skipIfDimensionsMatch')
                                ->setDeprecated('contao/core-bundle', '4.9', 'Using contao.image.sizes.*.skipIfDimensionsMatch is deprecated. Please use contao.image.sizes.*.skip_if_dimensions_match instead.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('target_dir')
                    ->info('The target directory for the cached images processed by Contao.')
                    ->example('%kernel.project_dir%/assets/images')
                    ->cannotBeEmpty()
                    ->defaultValue(Path::join($this->projectDir, 'assets/images'))
                    ->validate()
                        ->always(static fn (string $value): string => Path::canonicalize($value))
                    ->end()
                ->end()
                ->scalarNode('target_path')
                    ->setDeprecated('contao/core-bundle', '4.9', 'Use the "contao.image.target_dir" parameter instead.')
                    ->defaultNull()
                ->end()
                ->arrayNode('valid_extensions')
                    ->prototype('scalar')->end()
                    ->defaultValue(['jpg', 'jpeg', 'gif', 'png', 'tif', 'tiff', 'bmp', 'svg', 'svgz', 'webp', 'avif'])
                ->end()
                ->arrayNode('preview')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('target_dir')
                            ->info('The target directory for the cached previews.')
                            ->example('%kernel.project_dir%/assets/previews')
                            ->cannotBeEmpty()
                            ->defaultValue(Path::join($this->projectDir, 'assets/previews'))
                            ->validate()
                                ->always(static fn (string $value): string => Path::canonicalize($value))
                            ->end()
                        ->end()
                        ->integerNode('default_size')
                            ->min(1)
                            ->max(65535)
                            ->defaultValue(512)
                        ->end()
                        ->integerNode('max_size')
                            ->min(1)
                            ->max(65535)
                            ->defaultValue(1024)
                        ->end()
                        ->booleanNode('enable_fallback_images')
                            ->info('Whether or not to generate previews for unsupported file types that show a file icon containing the file type.')
                            ->defaultValue(true)
                        ->end()
                    ->end()
                    ->validate()
                        ->ifTrue(static fn (array $v) => $v['default_size'] > $v['max_size'])
                        ->thenInvalid('The default_size must not be greater than the max_size: %s')
                    ->end()
                ->end()
                ->arrayNode('preserve_metadata_fields')
                    ->info('Which metadata fields to preserve when resizing images.')
                    ->example([ExifFormat::NAME => ExifFormat::DEFAULT_PRESERVE_KEYS, IptcFormat::NAME => IptcFormat::DEFAULT_PRESERVE_KEYS])
                    ->defaultValue((new ResizeOptions())->getPreserveCopyrightMetadata())
                    ->useAttributeAsKey('format')
                    ->arrayPrototype()
                        ->beforeNormalization()->castToArray()->end()
                        ->scalarPrototype()->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addImagineOptionsNode(bool $withDefaults): NodeDefinition
    {
        $node = (new TreeBuilder('imagine_options'))
            ->getRootNode()
            ->children()
                ->integerNode('jpeg_quality')
                ->end()
                ->arrayNode('jpeg_sampling_factors')
                    ->prototype('scalar')->end()
                ->end()
                ->integerNode('png_compression_level')
                ->end()
                ->integerNode('png_compression_filter')
                ->end()
                ->integerNode('webp_quality')
                ->end()
                ->booleanNode('webp_lossless')
                ->end()
                ->integerNode('avif_quality')
                ->end()
                ->booleanNode('avif_lossless')
                ->end()
                ->integerNode('heic_quality')
                ->end()
                ->booleanNode('heic_lossless')
                ->end()
                ->integerNode('jxl_quality')
                ->end()
                ->booleanNode('jxl_lossless')
                ->end()
                ->booleanNode('flatten')
                    ->info('Allows to disable the layer flattening of animated images. Set this option to false to support animations. It has no effect with Gd as Imagine service.')
                ->end()
                ->scalarNode('interlace')
                ->end()
            ->end()
        ;

        if ($withDefaults) {
            $node->addDefaultsIfNotSet();
            $node->find('jpeg_quality')->defaultValue(80);
            $node->find('jpeg_sampling_factors')->defaultValue([2, 1, 1]);
            $node->find('interlace')->defaultValue(ImageInterface::INTERLACE_PLANE);
        } else {
            $node
                ->validate()
                    ->always(
                        static function ($values) {
                            if (empty($values['jpeg_sampling_factors'])) {
                                unset($values['jpeg_sampling_factors']);
                            }

                            return $values;
                        },
                    )
                ->end()
            ;
        }

        return $node;
    }

    private function addIntlNode(): NodeDefinition
    {
        return (new TreeBuilder('intl'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('locales')
                    ->info('Adds, removes or overwrites the list of ICU locale IDs. Defaults to all locale IDs known to the system.')
                    ->prototype('scalar')->end()
                    ->defaultValue([])
                    ->example(['+de', '-de_AT', 'gsw_CH'])
                    ->validate()
                        ->ifTrue(
                            static function (array $locales): bool {
                                foreach ($locales as $locale) {
                                    if (!preg_match('/^[+-]?[a-z]{2}/', $locale)) {
                                        return true;
                                    }

                                    $locale = ltrim($locale, '+-');

                                    if (LocaleUtil::canonicalize($locale) !== $locale) {
                                        return true;
                                    }
                                }

                                return false;
                            },
                        )
                        ->thenInvalid('All provided locales must be in the canonicalized ICU form and optionally start with +/- to add/remove the locale to/from the default list.')
                    ->end()
                ->end()
                ->arrayNode('enabled_locales')
                    ->info('Adds, removes or overwrites the list of enabled locale IDs that can be used in the Backend for example. Defaults to all languages for which a translation exists.')
                    ->prototype('scalar')->end()
                    ->defaultValue([])
                    ->example(['+de', '-de_AT', 'gsw_CH'])
                    ->validate()
                        ->ifTrue(
                            static function (array $locales): bool {
                                foreach ($locales as $locale) {
                                    if (!preg_match('/^[+-]?[a-z]{2}/', $locale)) {
                                        return true;
                                    }

                                    $locale = ltrim($locale, '+-');

                                    if (LocaleUtil::canonicalize($locale) !== $locale) {
                                        return true;
                                    }
                                }

                                return false;
                            },
                        )
                        ->thenInvalid('All provided locales must be in the canonicalized ICU form and optionally start with +/- to add/remove the locale to/from the default list.')
                    ->end()
                ->end()
                ->arrayNode('countries')
                    ->info('Adds, removes or overwrites the list of ISO 3166-1 alpha-2 country codes.')
                    ->prototype('scalar')->end()
                    ->defaultValue([])
                    ->example(['+DE', '-AT', 'CH'])
                    ->validate()
                        ->ifTrue(
                            static function (array $countries): bool {
                                foreach ($countries as $country) {
                                    if (!preg_match('/^[+-]?[A-Z][A-Z0-9]$/', $country)) {
                                        return true;
                                    }
                                }

                                return false;
                            },
                        )
                        ->thenInvalid('All provided countries must be two uppercase letters and optionally start with +/- to add/remove the country to/from the default list.')
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addSecurityNode(): NodeDefinition
    {
        return (new TreeBuilder('security'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('two_factor')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enforce_backend')
                            ->defaultValue(false)
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addSearchNode(): NodeDefinition
    {
        return (new TreeBuilder('search'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('default_indexer')
                    ->info('The default search indexer, which indexes pages in the database.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enable')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
                ->booleanNode('index_protected')
                    ->info('Enables indexing of protected pages.')
                    ->defaultFalse()
                ->end()
                ->arrayNode('listener')
                    ->info('The search index listener can index valid and delete invalid responses upon every request. You may limit it to one of the features or disable it completely.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('index')
                            ->info('Enables indexing successful responses.')
                            ->defaultTrue()
                        ->end()
                        ->booleanNode('delete')
                            ->info('Enables deleting unsuccessful responses from the index.')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addCrawlNode(): NodeDefinition
    {
        return (new TreeBuilder('crawl'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('additional_uris')
                    ->info('Additional URIs to crawl. By default, only the ones defined in the root pages are crawled.')
                    ->validate()
                    ->ifTrue(
                        static function (array $uris): bool {
                            foreach ($uris as $uri) {
                                if (!preg_match('@^https?://@', $uri)) {
                                    return true;
                                }
                            }

                            return false;
                        },
                    )
                    ->thenInvalid('All provided additional URIs must start with either http:// or https://.')
                    ->end()
                    ->prototype('scalar')->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('default_http_client_options')
                    ->info('Allows to configure the default HttpClient options (useful for proxy settings, SSL certificate validation and more).')
                    ->prototype('variable')->end()
                    ->defaultValue([])
                ->end()
            ->end()
        ;
    }

    private function addMailerNode(): NodeDefinition
    {
        return (new TreeBuilder('mailer'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('transports')
                    ->info('Specifies the mailer transports available for selection within Contao.')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('from')
                                ->info('Overrides the "From" address for any e-mails sent with this mailer transport.')
                                ->defaultNull()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addBackendNode(): NodeDefinition
    {
        return (new TreeBuilder('backend'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('attributes')
                    ->info('Adds HTML attributes to the <body> tag in the back end.')
                    ->example(['app-name' => 'My App', 'app-version' => '1.2.3'])
                    ->validate()
                    ->always(
                        static function (array $attributes): array {
                            foreach (array_keys($attributes) as $name) {
                                if (preg_match('/[^a-z0-9\-.:_]/', (string) $name)) {
                                    throw new \InvalidArgumentException(sprintf('The attribute name "%s" must be a valid HTML attribute name.', $name));
                                }
                            }

                            return $attributes;
                        },
                    )
                    ->end()
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('custom_css')
                    ->info('Adds custom style sheets to the back end.')
                    ->example(['files/backend/custom.css'])
                    ->cannotBeEmpty()
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('custom_js')
                    ->info('Adds custom JavaScript files to the back end.')
                    ->example(['files/backend/custom.js'])
                    ->cannotBeEmpty()
                    ->scalarPrototype()->end()
                ->end()
                ->scalarNode('badge_title')
                    ->info('Configures the title of the badge in the back end.')
                    ->example('develop')
                    ->cannotBeEmpty()
                    ->defaultValue('')
                ->end()
                ->scalarNode('route_prefix')
                    ->info('Defines the path of the Contao backend.')
                    ->validate()
                        ->ifTrue(static fn (string $prefix) => 1 !== preg_match('/^\/\S*[^\/]$/', $prefix))
                        ->thenInvalid('The backend path must begin but not end with a slash. Invalid path configured: %s')
                    ->end()
                    ->example('/admin')
                    ->defaultValue('/contao')
                ->end()
            ->end()
        ;
    }

    private function addInsertTagsNode(): NodeDefinition
    {
        return (new TreeBuilder('insert_tags'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('allowed_tags')
                    ->info('A list of allowed insert tags.')
                    ->example(['*_url', 'request_token'])
                    ->scalarPrototype()->end()
                    ->defaultValue(['*'])
                ->end()
            ->end()
        ;
    }

    private function addBackupNode(): NodeDefinition
    {
        return (new TreeBuilder('backup'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('ignore_tables')
                    ->info('These tables are ignored by default when creating and restoring backups.')
                    ->defaultValue(['tl_crawl_queue', 'tl_log', 'tl_search', 'tl_search_index', 'tl_search_term'])
                    ->scalarPrototype()->end()
                ->end()
                ->integerNode('keep_max')
                    ->info('The maximum number of backups to keep. Use 0 to keep all the backups forever.')
                    ->defaultValue(5)
                ->end()
                ->arrayNode('keep_intervals')
                    ->info('The latest backup plus the oldest of every configured interval will be kept. Intervals have to be specified as documented in https://www.php.net/manual/en/dateinterval.construct.php without the P prefix.')
                    ->defaultValue(['1D', '7D', '14D', '1M'])
                    ->validate()
                        ->ifTrue(
                            static function (array $intervals) {
                                try {
                                    RetentionPolicy::validateAndSortIntervals($intervals);
                                } catch (\Exception) {
                                    return true;
                                }

                                return false;
                            },
                        )
                    ->thenInvalid('%s')
                    ->end()
                    ->scalarPrototype()->end()
                ->end()
            ->end()
        ;
    }

    private function addSanitizerNode(): NodeDefinition
    {
        return (new TreeBuilder('sanitizer'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('allowed_url_protocols')
                    ->prototype('scalar')->end()
                    ->defaultValue(['http', 'https', 'ftp', 'mailto', 'tel', 'data', 'skype', 'whatsapp'])
                    ->validate()
                        ->always(
                            static function (array $protocols): array {
                                foreach ($protocols as $protocol) {
                                    if (!preg_match('/^[a-z][a-z0-9\-+.]*$/i', (string) $protocol)) {
                                        throw new \InvalidArgumentException(sprintf('The protocol name "%s" must be a valid URI scheme.', $protocol));
                                    }
                                }

                                return $protocols;
                            },
                        )
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addCronNode(): NodeDefinition
    {
        return (new TreeBuilder('cron'))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->enumNode('web_listener')
                    ->info('Allows to enable or disable the kernel.terminate listener that executes cron jobs within the web process. "auto" will auto-disable it if a CLI cron is running.')
                    ->values(['auto', true, false])
                    ->defaultValue('auto')
                ->end()
            ->end()
        ;
    }
}
