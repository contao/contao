<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Contao\CoreBundle\Util\LocaleUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Intl\Countries as SymfonyCountries;

class IntlInstalledLocalesAndCountriesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->has('contao.intl.locales')) {
            $definition = $container->findDefinition('contao.intl.locales');

            $enabledLocales = $this->getEnabledLocales($container);
            $locales = array_values(array_unique([...$enabledLocales, ...$this->getDefaultLocales()]));

            $definition->setArgument(2, $locales);
            $definition->setArgument(3, $enabledLocales);
        }

        if ($container->has('contao.intl.countries')) {
            $container->findDefinition('contao.intl.countries')->setArgument(2, SymfonyCountries::getCountryCodes());
        }
    }

    /**
     * @return array<int, string>
     */
    private function getEnabledLocales(ContainerBuilder $container): array
    {
        $projectDir = $container->getParameter('kernel.project_dir');
        $defaultLocale = $container->getParameter('kernel.default_locale');

        $dirs = [__DIR__.'/../../../contao/languages'];

        if (is_dir($path = Path::join($projectDir, 'contao/languages'))) {
            $dirs[] = $path;
        }

        // The default locale must be the first supported language (see contao/core#6533)
        $languages = [$defaultLocale];

        $finder = Finder::create()->directories()->depth(0)->name('/^[a-z]{2,}/')->in($dirs);

        foreach ($finder as $file) {
            $locale = $file->getFilename();

            if (LocaleUtil::canonicalize($locale) !== $locale) {
                continue;
            }

            $languages[] = $locale;
        }

        return array_values(array_unique($languages));
    }

    private function getDefaultLocales(): array
    {
        $allLocales = [];
        $resourceBundle = \ResourceBundle::create('supplementalData', 'ICUDATA', false);

        foreach ($resourceBundle['territoryInfo'] ?? [] as $data) {
            foreach ($data as $language => $info) {
                if (\Locale::getDisplayName($language, 'en') === $language) {
                    continue;
                }

                if ('official_regional' === ($info['officialStatus'] ?? null)) {
                    $allLocales[] = $language;
                }
            }
        }

        foreach ($resourceBundle['languageData'] ?? [] as $language => $data) {
            if (
                (!$regions = ($data['primary']['territories'] ?? null))
                || \Locale::getDisplayName($language, 'en') === $language
            ) {
                continue;
            }

            $scripts = $data['primary']['scripts'] ?? [];
            $locales = [$language];

            if (!\is_string($scripts) && \count($scripts) > 1) {
                foreach ($scripts as $script) {
                    $locales[] = "{$language}_$script";
                }
            }

            foreach ($locales as $locale) {
                $allLocales[] = $locale;

                foreach (\is_string($regions) ? [$regions] : $regions as $region) {
                    $allLocales[] = "{$locale}_$region";
                }
            }
        }

        return $allLocales ?: \ResourceBundle::getLocales('');
    }
}
