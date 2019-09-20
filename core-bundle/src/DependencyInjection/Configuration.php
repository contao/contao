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
                    ->min(-1)
                    ->max(32767)
                    ->defaultValue(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED)
                ->end()
                ->variableNode('localconfig')
                ->end()
                ->arrayNode('locales')
                    ->prototype('scalar')->end()
                    ->defaultValue($this->getLocales())
                ->end()
                ->booleanNode('prepend_locale')
                    ->defaultFalse()
                ->end()
                ->booleanNode('pretty_error_screens')
                    ->defaultValue(false)
                ->end()
                ->scalarNode('preview_script')
                    ->cannotBeEmpty()
                    ->defaultValue('')
                    ->validate()
                        ->always(
                            function (string $value): string {
                                return $this->canonicalize($value);
                            }
                        )
                    ->end()
                ->end()
                ->scalarNode('upload_path')
                    ->cannotBeEmpty()
                    ->defaultValue('files')
                    ->validate()
                        ->ifTrue(
                            static function (string $v): int {
                                return preg_match(
                                    '@^(app|assets|bin|config|contao|plugins|share|system|templates|var|vendor|web)(/|$)@',
                                    $v
                                );
                            }
                        )
                        ->thenInvalid('%s')
                    ->end()
                ->end()
                ->scalarNode('url_suffix')
                    ->defaultValue('.html')
                ->end()
                ->scalarNode('web_dir')
                    ->cannotBeEmpty()
                    ->defaultValue($this->canonicalize($this->projectDir.'/web'))
                    ->validate()
                        ->always(
                            function (string $value): string {
                                return $this->canonicalize($value);
                            }
                        )
                    ->end()
                ->end()
                ->append($this->addImageNode())
                ->append($this->addSecurityNode())
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
                    ->defaultNull()
                    ->info('Contao automatically detects the best Imagine service out of Gmagick, Imagick and Gd (in this order). To use a specific service, set its service ID here.')
                ->end()
                ->booleanNode('reject_large_uploads')
                    ->defaultValue(false)
                ->end()
                ->arrayNode('sizes')
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

                                foreach ($value as $name => $config) {
                                    if (preg_match('/^\d+$/', (string) $name)) {
                                        throw new \InvalidArgumentException(
                                            sprintf(
                                                'The image size name "%s" cannot contain only digits',
                                                $name
                                            )
                                        );
                                    }

                                    if (\in_array($name, $reservedImageSizeNames, true)) {
                                        throw new \InvalidArgumentException(
                                            sprintf(
                                                '"%s" is a reserved image size name (reserved names: %s)',
                                                $name,
                                                implode(', ', $reservedImageSizeNames)
                                            )
                                        );
                                    }

                                    if (preg_match('/[^a-z0-9_]/', (string) $name)) {
                                        throw new \InvalidArgumentException(
                                            sprintf(
                                                'The image size name "%s" must consist of lowercase letters, digits and underscores only',
                                                $name
                                            )
                                        );
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
                            ->enumNode('resizeMode')
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
                            ->scalarNode('cssClass')
                            ->end()
                            ->booleanNode('lazyLoading')
                            ->end()
                            ->scalarNode('densities')
                            ->end()
                            ->scalarNode('sizes')
                            ->end()
                            ->booleanNode('skipIfDimensionsMatch')
                            ->end()
                            ->arrayNode('formats')
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
                                        ->enumNode('resizeMode')
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
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('target_dir')
                    ->cannotBeEmpty()
                    ->defaultValue($this->canonicalize($this->projectDir.'/assets/images'))
                    ->validate()
                        ->always(
                            function (string $value): string {
                                return $this->canonicalize($value);
                            }
                        )
                    ->end()
                ->end()
                ->scalarNode('target_path')
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

    /**
     * Canonicalizes a path preserving the directory separators.
     */
    private function canonicalize(string $value): string
    {
        $resolved = [];
        $chunks = preg_split('#([\\\\/]+)#', $value, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        for ($i = 0, $c = \count($chunks); $i < $c; ++$i) {
            if ('.' === $chunks[$i]) {
                ++$i;
                continue;
            }

            // Reduce multiple slashes to one
            if (0 === strncmp($chunks[$i], '/', 1)) {
                $resolved[] = '/';
                continue;
            }

            // Reduce multiple backslashes to one
            if (0 === strncmp($chunks[$i], '\\', 1)) {
                $resolved[] = '\\';
                continue;
            }

            if ('..' === $chunks[$i]) {
                ++$i;
                array_pop($resolved);
                array_pop($resolved);
                continue;
            }

            $resolved[] = $chunks[$i];
        }

        return rtrim(implode('', $resolved), '\/');
    }

    /**
     * @return string[]
     */
    private function getLocales(): array
    {
        $dirs = [__DIR__.'/../Resources/contao/languages'];

        if (is_dir($this->projectDir.'/contao/languages')) {
            $dirs[] = $this->projectDir.'/contao/languages';
        }

        // Backwards compatibility
        if (is_dir($this->projectDir.'/app/Resources/contao/languages')) {
            $dirs[] = $this->projectDir.'/app/Resources/contao/languages';
        }

        // The default locale must be the first supported language (see contao/core#6533)
        $languages = [$this->defaultLocale];

        /** @var SplFileInfo[] $finder */
        $finder = Finder::create()->directories()->depth(0)->name('/^[a-z]{2}(_[A-Z]{2})?$/')->in($dirs);

        foreach ($finder as $file) {
            $languages[] = $file->getFilename();
        }

        return array_values(array_unique($languages));
    }
}
