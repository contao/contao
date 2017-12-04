<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection;

use Imagine\Image\ImageInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Configuration implements ConfigurationInterface
{
    /**
     * @var bool
     */
    private $debug;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var string
     */
    private $defaultLocale;

    /**
     * @param bool   $debug
     * @param string $projectDir
     * @param string $rootDir
     * @param string $defaultLocale
     */
    public function __construct(bool $debug, string $projectDir, string $rootDir, string $defaultLocale)
    {
        $this->debug = $debug;
        $this->projectDir = $projectDir;
        $this->rootDir = $rootDir;
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('contao');

        $rootNode
            ->children()
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
                ->booleanNode('prepend_locale')
                    ->defaultFalse()
                ->end()
                ->scalarNode('encryption_key')
                    ->cannotBeEmpty()
                    ->defaultValue('%kernel.secret%')
                ->end()
                ->scalarNode('url_suffix')
                    ->defaultValue('.html')
                ->end()
                ->scalarNode('upload_path')
                    ->cannotBeEmpty()
                    ->defaultValue('files')
                    ->validate()
                        ->ifTrue(
                            function (string $v): int {
                                return preg_match(
                                    '@^(app|assets|bin|contao|plugins|share|system|templates|var|vendor|web)(/|$)@',
                                    $v
                                );
                            }
                        )
                        ->thenInvalid('%s')
                    ->end()
                ->end()
                ->scalarNode('csrf_token_name')
                    ->cannotBeEmpty()
                    ->defaultValue('contao_csrf_token')
                ->end()
                ->booleanNode('pretty_error_screens')
                    ->defaultValue(!$this->debug)
                ->end()
                ->integerNode('error_level')
                    ->min(-1)
                    ->max(32767)
                    ->defaultValue(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED)
                ->end()
                ->arrayNode('locales')
                    ->prototype('scalar')->end()
                    ->defaultValue($this->getLocales())
                ->end()
                ->arrayNode('image')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('bypass_cache')
                            ->defaultValue(false)
                        ->end()
                        ->scalarNode('target_path')
                            ->defaultNull()
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
                        ->arrayNode('valid_extensions')
                            ->prototype('scalar')->end()
                            ->defaultValue(['jpg', 'jpeg', 'gif', 'png', 'tif', 'tiff', 'bmp', 'svg', 'svgz'])
                        ->end()
                        ->arrayNode('imagine_options')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->integerNode('jpeg_quality')
                                    ->defaultValue(80)
                                ->end()
                                ->scalarNode('interlace')
                                    ->defaultValue(ImageInterface::INTERLACE_PLANE)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('security')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('disable_ip_check')
                            ->defaultFalse()
                        ->end()
                    ->end()
                ->end()
                ->variableNode('localconfig')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * Canonicalizes a path preserving the directory separators.
     *
     * @param string $value
     *
     * @return string
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
            if ('/' === $chunks[$i][0]) {
                $resolved[] = '/';
                continue;
            }

            // Reduce multiple backslashes to one
            if ('\\' === $chunks[$i][0]) {
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

        return rtrim(implode($resolved), '\/');
    }

    /**
     * Returns the Contao locales.
     *
     * @return array
     */
    private function getLocales(): array
    {
        $dirs = [__DIR__.'/../Resources/contao/languages'];

        // app/Resources/contao/languages
        if (is_dir($this->rootDir.'/Resources/contao/languages')) {
            $dirs[] = $this->rootDir.'/Resources/contao/languages';
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
