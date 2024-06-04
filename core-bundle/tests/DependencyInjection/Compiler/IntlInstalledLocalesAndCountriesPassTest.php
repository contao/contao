<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\ArrayUtil;
use Contao\CoreBundle\DependencyInjection\Compiler\IntlInstalledLocalesAndCountriesPass;
use Contao\CoreBundle\Intl\Countries;
use Contao\CoreBundle\Intl\Locales;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class IntlInstalledLocalesAndCountriesPassTest extends TestCase
{
    public function testDoesNothingIfThereIsService(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive(['contao.intl.locales'], ['contao.intl.countries'])
            ->willReturn(false)
        ;

        $pass = new IntlInstalledLocalesAndCountriesPass();
        $pass->process($container);
    }

    public function testAddsLocalesArguments(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('contao.intl.locales', new Definition(Locales::class, []));
        $container->setParameter('contao.locales', []);
        $container->setParameter('kernel.project_dir', __DIR__);
        $container->setParameter('kernel.default_locale', 'en');

        $pass = new IntlInstalledLocalesAndCountriesPass();
        $pass->process($container);

        $availableLocales = $container->getDefinition('contao.intl.locales')->getArgument(2);
        $enabledLocales = $container->getDefinition('contao.intl.locales')->getArgument(3);

        $this->assertIsArray($availableLocales);
        $this->assertNotEmpty($availableLocales);
        $this->assertFalse(ArrayUtil::isAssoc($availableLocales));

        foreach ($availableLocales as $localeId) {
            $this->assertMatchesRegularExpression('/^[a-z]{2}/', $localeId);
        }

        $this->assertIsArray($enabledLocales);
        $this->assertNotEmpty($enabledLocales);
        $this->assertFalse(ArrayUtil::isAssoc($enabledLocales));

        foreach ($enabledLocales as $localeId) {
            $this->assertMatchesRegularExpression('/^[a-z]{2}/', $localeId);
        }
    }

    public function testAddsCountriesArguments(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('contao.intl.countries', new Definition(Countries::class, []));

        $pass = new IntlInstalledLocalesAndCountriesPass();
        $pass->process($container);

        $availableCountries = $container->getDefinition('contao.intl.countries')->getArgument(2);

        $this->assertIsArray($availableCountries);
        $this->assertNotEmpty($availableCountries);
        $this->assertFalse(ArrayUtil::isAssoc($availableCountries));

        foreach ($availableCountries as $country) {
            $this->assertMatchesRegularExpression('/^[A-Z]{2}$/', $country);
        }
    }
}
