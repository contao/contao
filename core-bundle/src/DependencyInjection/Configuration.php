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

use Contao\Image\ResizeConfiguration;
use Imagine\Image\ImageInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Webmozart\PathUtil\Path;

class Configuration implements ConfigurationInterface
{
    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var string
     */
    private $defaultLocale;

    public function __construct(string $projectDir, string $defaultLocale)
    {
        $this->projectDir = $projectDir;
        $this->defaultLocale = $defaultLocale;
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
                ->scalarNode('encryption_key')
                    ->cannotBeEmpty()
                    ->defaultValue('%kernel.secret%')
                ->end()
                ->integerNode('error_level')
                    ->info('The error reporting level set when the framework is initialized. Defaults to E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED.')
                    ->min(-1)
                    ->max(32767)
                    ->defaultValue(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED)
                ->end()
                ->booleanNode('legacy_routing')
                    ->defaultTrue()
                    ->info('Disabling legacy routing allows to configure the URL prefix and suffix per root page. However, it might not be compatible with third-party extensions.')
                ->end()
                ->variableNode('localconfig')
                    ->info('Allows to set TL_CONFIG variables, overriding settings stored in localconfig.php. Changes in the Contao back end will not have any effect.')
                ->end()
                ->arrayNode('locales')
                    ->info('Allows to configure which languages can be used in the Contao back end. Defaults to all languages for which a translation exists.')
                    ->prototype('scalar')->end()
                    ->defaultValue($this->getLocales())
                ->end()
                ->booleanNode('prepend_locale')
                    ->info('Whether or not to add the page language to the URL.')
                    ->setDeprecated(...$this->getDeprecationArgs('contao/core-bundle', '4.10', 'The URL prefix is configured per root page since Contao 4.10. Using this option requires legacy routing.'))
                    ->defaultFalse()
                ->end()
                ->booleanNode('pretty_error_screens')
                    ->info('Show customizable, pretty error screens instead of the default PHP error messages.')
                    ->defaultValue(false)
                ->end()
                ->scalarNode('preview_script')
                    ->info('An optional entry point script that bypasses the front end cache for previewing changes (e.g. preview.php).')
                    ->cannotBeEmpty()
                    ->defaultValue('')
                    ->validate()
                        ->always(
                            static function (string $value): string {
                                return Path::canonicalize($value);
                            }
                        )
                    ->end()
                ->end()
                ->scalarNode('upload_path')
                    ->info('The folder used by the file manager.')
                    ->cannotBeEmpty()
                    ->defaultValue('files')
                    ->validate()
                        ->ifTrue(
                            static function (string $v): int {
                                return preg_match('@^(app|assets|bin|config|contao|plugins|share|system|templates|var|vendor|web)(/|$)@', $v);
                            }
                        )
                        ->thenInvalid('%s')
                    ->end()
                ->end()
                ->scalarNode('editable_files')
                    ->defaultValue('css,csv,html,ini,js,json,less,md,scss,svg,svgz,txt,xliff,xml,yml,yaml')
                ->end()
                ->scalarNode('url_suffix')
                    ->setDeprecated(...$this->getDeprecationArgs('contao/core-bundle', '4.10', 'The URL suffix is configured per root page since Contao 4.10. Using this option requires legacy routing.'))
                    ->defaultValue('.html')
                ->end()
                ->scalarNode('web_dir')
                    ->info('Absolute path to the web directory. Defaults to %kernel.project_dir%/web.')
                    ->cannotBeEmpty()
                    ->defaultValue(Path::join($this->projectDir, 'web'))
                    ->validate()
                        ->always(
                            static function (string $value): string {
                                return Path::canonicalize($value);
                            }
                        )
                    ->end()
                ->end()
                ->append($this->addImageNode())
                ->append($this->addSecurityNode())
                ->append($this->addSearchNode())
                ->append($this->addCrawlNode())
                ->append($this->addMailerNode())
                ->append($this->addBackendNode())
            ->end()
        ;

        return $treeBuilder;
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
                ->arrayNode('imagine_options')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('jpeg_quality')
                            ->defaultValue(80)
                        ->end()
                        ->arrayNode('jpeg_sampling_factors')
                            ->prototype('scalar')->end()
                            ->defaultValue([2, 1, 1])
                        ->end()
                        ->integerNode('png_compression_level')
                        ->end()
                        ->integerNode('png_compression_filter')
                        ->end()
                        ->integerNode('webp_quality')
                        ->end()
                        ->booleanNode('webp_lossless')
                        ->end()
                        ->scalarNode('interlace')
                            ->defaultValue(ImageInterface::INTERLACE_PLANE)
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('imagine_service')
                    ->info('Contao automatically uses an Imagine service out of Gmagick, Imagick and Gd (in this order). Set a service ID here to override.')
                    ->defaultNull()
                ->end()
                ->booleanNode('reject_large_uploads')
                    ->info('Reject uploaded images exceeding the localconfig.gdMaxImgWidth and localconfig.gdMaxImgHeight dimensions.')
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
                            }
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
                                ->example(['jpg' => ['webp', 'jpg'], 'gif' => ['png']])
                                ->useAttributeAsKey('source')
                                ->arrayPrototype()
                                    ->beforeNormalization()->castToArray()->end()
                                    ->scalarPrototype()->end()
                                ->end()
                            ->end()
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
                                            ->setDeprecated(...$this->getDeprecationArgs('contao/core-bundle', '4.9', 'Using contao.image.sizes.*.items.resizeMode is deprecated. Please use contao.image.sizes.*.items.resize_mode instead.'))
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
                                ->setDeprecated(...$this->getDeprecationArgs('contao/core-bundle', '4.9', 'Using contao.image.sizes.*.resizeMode is deprecated. Please use contao.image.sizes.*.resize_mode instead.'))
                                ->values([
                                    ResizeConfiguration::MODE_CROP,
                                    ResizeConfiguration::MODE_BOX,
                                    ResizeConfiguration::MODE_PROPORTIONAL,
                                ])
                            ->end()
                            ->scalarNode('cssClass')
                                ->setDeprecated(...$this->getDeprecationArgs('contao/core-bundle', '4.9', 'Using contao.image.sizes.*.cssClass is deprecated. Please use contao.image.sizes.*.css_class instead.'))
                            ->end()
                            ->booleanNode('lazyLoading')
                                ->setDeprecated(...$this->getDeprecationArgs('contao/core-bundle', '4.9', 'Using contao.image.sizes.*.lazyLoading is deprecated. Please use contao.image.sizes.*.lazy_loading instead.'))
                            ->end()
                            ->booleanNode('skipIfDimensionsMatch')
                                ->setDeprecated(...$this->getDeprecationArgs('contao/core-bundle', '4.9', 'Using contao.image.sizes.*.skipIfDimensionsMatch is deprecated. Please use contao.image.sizes.*.skip_if_dimensions_match instead.'))
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
                        ->always(
                            static function (string $value): string {
                                return Path::canonicalize($value);
                            }
                        )
                    ->end()
                ->end()
                ->scalarNode('target_path')
                    ->setDeprecated(...$this->getDeprecationArgs('contao/core-bundle', '4.9', 'Use the "contao.image.target_dir" parameter instead.'))
                    ->defaultNull()
                ->end()
                ->arrayNode('valid_extensions')
                    ->prototype('scalar')->end()
                    ->defaultValue(['jpg', 'jpeg', 'gif', 'png', 'tif', 'tiff', 'bmp', 'svg', 'svgz', 'webp'])
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
                        ->scalarNode('enable')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('index_protected')
                    ->info('Enables indexing of protected pages.')
                    ->defaultFalse()
                ->end()
                ->arrayNode('listener')
                    ->info('The search index listener can index valid and delete invalid responses upon every request. You may limit it to one of the features or disable it completely.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('index')
                            ->info('Enables indexing successful responses.')
                            ->defaultTrue()
                        ->end()
                        ->scalarNode('delete')
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
                        }
                    )
                    ->thenInvalid('All provided additional URIs must start with either http:// or https://.')
                    ->end()
                    ->prototype('scalar')->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('default_http_client_options')
                    ->info('Allows to configure the default HttpClient options (useful for proxy settings, SSL certificate validation and more).')
                    ->prototype('scalar')->end()
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
                    ->example(['data-app-name' => 'My App', 'data-app-version' => '1.2.3'])
                    ->validate()
                    ->always(
                        static function (array $attributes): array {
                            foreach (array_keys($attributes) as $name) {
                                if (preg_match('/[^a-z0-9\-.:_]/', (string) $name)) {
                                    throw new \InvalidArgumentException(sprintf('The attribute name "%s" must be a valid HTML attribute name.', $name));
                                }
                            }

                            return $attributes;
                        }
                    )
                    ->end()
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->scalarPrototype()->end()
                ->end()
                ->scalarNode('theme')
                    ->info('Set a backend theme for all users.')
                    ->defaultNull()
                ->end()
                ->scalarNode('theme_path')
                    ->info('Point to Encore\'s entrypoints.json of your custom backend theme.')
                    ->validate()
                    ->always(
                        static function ($value) {
                            if (!file_exists($value)) {
                                throw new \InvalidArgumentException(sprintf('The file "%s" does not exist.', $value));
                            }

                            return $value;
                        }
                    )
                    ->end()
                    ->example('%kernel.project_dir%/web/layout/backend/entrypoints.json')
                    ->defaultNull()
                ->end()
                ->arrayNode('custom_css')
                    ->info('Adds custom style sheets to the back end.')
                    ->example(['files/backend/custom.css'])
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('custom_js')
                    ->info('Adds custom JavaScript files to the back end.')
                    ->example(['files/backend/custom.js'])
                    ->scalarPrototype()->end()
                ->end()
                ->scalarNode('badge_title')
                    ->info('Configures the title of the badge in the back end.')
                    ->example('develop')
                    ->defaultValue('')
                ->end()
            ->end()
        ;
    }

    /**
     * @return array<string>
     */
    private function getLocales(): array
    {
        $dirs = [__DIR__.'/../Resources/contao/languages'];

        if (is_dir($path = Path::join($this->projectDir, 'contao/languages'))) {
            $dirs[] = $path;
        }

        // Backwards compatibility
        if (is_dir($path = Path::join($this->projectDir, 'app/Resources/contao/languages'))) {
            $dirs[] = $path;
        }

        // The default locale must be the first supported language (see contao/core#6533)
        $languages = [$this->defaultLocale];

        /** @var array<SplFileInfo> $finder */
        $finder = Finder::create()->directories()->depth(0)->name('/^[a-z]{2}(_[A-Z]{2})?$/')->in($dirs);

        foreach ($finder as $file) {
            $languages[] = $file->getFilename();
        }

        return array_values(array_unique($languages));
    }

    /**
     * @return array<string>
     *
     * @todo Remove this as soon as we are on Symfony 5 only
     */
    private function getDeprecationArgs(string $package, string $version, string $message): array
    {
        /** @phpstan-ignore-next-line */
        if (method_exists('root', TreeBuilder::class)) {
            return [$message];
        }

        return [$package, $version, $message];
    }
}
