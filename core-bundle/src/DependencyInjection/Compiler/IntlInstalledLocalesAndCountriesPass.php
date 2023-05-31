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
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Intl\Countries as SymfonyCountries;

class IntlInstalledLocalesAndCountriesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->has('contao.intl.locales')) {
            $definition = $container->findDefinition('contao.intl.locales');

            $enabledLocales = $this->getEnabledLocales($container);
            $locales = array_values(array_unique([...$enabledLocales, ...\ResourceBundle::getLocales('')]));

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

        /** @var array<SplFileInfo> $finder */
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
}
