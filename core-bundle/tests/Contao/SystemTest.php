<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Intl\Countries;
use Contao\CoreBundle\Intl\Locales;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Translation\Translator;
use Contao\System;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SystemTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_LANG']);

        $this->resetStaticProperties([System::class]);

        (new Filesystem())->remove($this->getTempDir());
    }

    public function testFormatsANumber(): void
    {
        $number = '12004.34564';

        // Override the settings
        $GLOBALS['TL_LANG']['MSC']['decimalSeparator'] = '.';
        $GLOBALS['TL_LANG']['MSC']['thousandsSeparator'] = '';

        $numbers = [
            0 => '12004',
            1 => '12004.3',
            2 => '12004.35',
            3 => '12004.346',
            4 => '12004.3456',
            5 => '12004.34564',
        ];

        foreach ($numbers as $decimals => $formatted) {
            $this->assertSame(System::getFormattedNumber($number, $decimals), $formatted);
        }

        // Override the thousands separator
        $GLOBALS['TL_LANG']['MSC']['thousandsSeparator'] = ',';

        $numbers = [
            0 => '12,004',
            1 => '12,004.3',
            2 => '12,004.35',
            3 => '12,004.346',
            4 => '12,004.3456',
            5 => '12,004.34564',
        ];

        foreach ($numbers as $decimals => $formatted) {
            $this->assertSame(System::getFormattedNumber($number, $decimals), $formatted);
        }
    }

    public function testAnonymizesIpAddresses(): void
    {
        $ipv4 = '172.16.254.112';
        $ipv6 = '2001:0db8:85a3:0042:0000:8a2e:0370:7334';

        $this->assertSame('172.16.254.0', System::anonymizeIp($ipv4));
        $this->assertSame('2001:0db8:85a3:0042:0000:8a2e:0370:0000', System::anonymizeIp($ipv6));
    }

    public function testLoadsLanguageFiles(): void
    {
        $tmpDir = $this->getTempDir();

        (new Filesystem())->dumpFile(
            "$tmpDir/contao/languages/en/default.php",
            '<?php $GLOBALS["TL_LANG"]["MSC"]["test"] = "Test English";'
            .'$GLOBALS["TL_LANG"]["MSC"]["order_test"] = "en";'
        );

        (new Filesystem())->dumpFile(
            "$tmpDir/contao/languages/de/default.php",
            '<?php $GLOBALS["TL_LANG"]["MSC"]["test"] = "Test deutsch";'
            .'$GLOBALS["TL_LANG"]["MSC"]["order_test"] .= "|de";'
        );

        (new Filesystem())->dumpFile(
            "$tmpDir/contao/languages/fr/default.php",
            '<?php $GLOBALS["TL_LANG"]["MSC"]["test"] = "Test français";'
            .'$GLOBALS["TL_LANG"]["MSC"]["order_test"] .= "|fr";'
        );

        $container = $this->getContainerWithContaoConfiguration($tmpDir);
        $container->setParameter('contao.resources_paths', ["$tmpDir/contao"]);

        System::setContainer($container);

        System::loadLanguageFile('default', 'en');

        $this->assertSame('Test English', $GLOBALS['TL_LANG']['MSC']['test']);
        $this->assertSame('en', $GLOBALS['TL_LANG']['MSC']['order_test']);

        System::loadLanguageFile('default', 'de');

        $this->assertSame('Test deutsch', $GLOBALS['TL_LANG']['MSC']['test']);
        $this->assertSame('en|de', $GLOBALS['TL_LANG']['MSC']['order_test']);

        System::loadLanguageFile('default', 'fr');

        $this->assertSame('Test français', $GLOBALS['TL_LANG']['MSC']['test']);
        $this->assertSame('en|fr', $GLOBALS['TL_LANG']['MSC']['order_test']);

        System::loadLanguageFile('does_not_exist', 'de');

        $this->assertSame('Test français', $GLOBALS['TL_LANG']['MSC']['test']);
        $this->assertSame('en|fr', $GLOBALS['TL_LANG']['MSC']['order_test']);

        System::loadLanguageFile('default', 'en');

        $this->assertSame('Test English', $GLOBALS['TL_LANG']['MSC']['test']);
        $this->assertSame('en', $GLOBALS['TL_LANG']['MSC']['order_test']);

        System::loadLanguageFile('default', 'fr');

        $this->assertSame('Test français', $GLOBALS['TL_LANG']['MSC']['test']);
        $this->assertSame('en|fr', $GLOBALS['TL_LANG']['MSC']['order_test']);

        System::loadLanguageFile('default', 'de');

        $this->assertSame('Test deutsch', $GLOBALS['TL_LANG']['MSC']['test']);
        $this->assertSame('en|de', $GLOBALS['TL_LANG']['MSC']['order_test']);

        System::loadLanguageFile('does_not_exist', 'fr');

        $this->assertSame('Test deutsch', $GLOBALS['TL_LANG']['MSC']['test']);
        $this->assertSame('en|de', $GLOBALS['TL_LANG']['MSC']['order_test']);

        (new Filesystem())->dumpFile(
            "$tmpDir/contao/languages/de/default.php",
            '<?php $GLOBALS["TL_LANG"]["MSC"]["test"] = "changed";'
            .'$GLOBALS["TL_LANG"]["MSC"]["order_test"] .= "changed";'
        );

        System::loadLanguageFile('does_not_exist', 'fr');
        System::loadLanguageFile('default', 'de');

        $this->assertSame(
            'Test deutsch',
            $GLOBALS['TL_LANG']['MSC']['test'],
            'Should have been cached, not loaded from the PHP file.'
        );

        $this->assertSame(
            'en|de',
            $GLOBALS['TL_LANG']['MSC']['order_test'],
            'Should have been cached, not loaded from the PHP file.'
        );
    }

    public function testLoadsLanguageTranslations(): void
    {
        $tmpDir = $this->getTempDir();

        (new Filesystem())->dumpFile(
            "$tmpDir/contao/languages/en/languages.php",
            '<?php '
            .'$GLOBALS["TL_LANG"]["LNG"]["en"] = "USA";'
        );

        (new Filesystem())->dumpFile(
            "$tmpDir/contao/languages/de/languages.php",
            '<?php '
            .'$GLOBALS["TL_LANG"]["LNG"]["en"] = "Ami";'
            .'$GLOBALS["TL_LANG"]["LNG"]["de"] = "Deu";'
        );

        (new Filesystem())->dumpFile(
            "$tmpDir/contao/languages/fr/languages.php",
            '<?php '
            .'$GLOBALS["TL_LANG"]["LNG"]["en"] = "Amé";'
            .'$GLOBALS["TL_LANG"]["LNG"]["fr"] = "Fra";'
        );

        System::setContainer($this->getContainerWithLocalesAndCountries($tmpDir));

        $referenceEn = &$GLOBALS['TL_LANG']['LNG']['en'];
        $referenceDe = &$GLOBALS['TL_LANG']['LNG']['de'];
        $referenceFr = &$GLOBALS['TL_LANG']['LNG']['fr'];

        System::loadLanguageFile('languages', 'en');

        $this->assertSame('USA', $GLOBALS['TL_LANG']['LNG']['en']);
        $this->assertSame('German', $GLOBALS['TL_LANG']['LNG']['de']);
        $this->assertSame('French', $GLOBALS['TL_LANG']['LNG']['fr']);
        $this->assertSame('USA', $referenceEn);
        $this->assertSame('German', $referenceDe);
        $this->assertSame('French', $referenceFr);

        System::loadLanguageFile('languages', 'de');

        $this->assertSame('Ami', $GLOBALS['TL_LANG']['LNG']['en']);
        $this->assertSame('Deu', $GLOBALS['TL_LANG']['LNG']['de']);
        $this->assertSame('Französisch', $GLOBALS['TL_LANG']['LNG']['fr']);
        $this->assertSame('Ami', $referenceEn);
        $this->assertSame('Deu', $referenceDe);
        $this->assertSame('Französisch', $referenceFr);

        System::loadLanguageFile('languages', 'fr');

        $this->assertSame('Amé', $GLOBALS['TL_LANG']['LNG']['en']);
        $this->assertSame('allemand', $GLOBALS['TL_LANG']['LNG']['de']);
        $this->assertSame('Fra', $GLOBALS['TL_LANG']['LNG']['fr']);
        $this->assertSame('Amé', $referenceEn);
        $this->assertSame('allemand', $referenceDe);
        $this->assertSame('Fra', $referenceFr);

        System::loadLanguageFile('languages', 'en');

        $this->assertSame('USA', $GLOBALS['TL_LANG']['LNG']['en']);
        $this->assertSame('German', $GLOBALS['TL_LANG']['LNG']['de']);
        $this->assertSame('French', $GLOBALS['TL_LANG']['LNG']['fr']);
        $this->assertSame('USA', $referenceEn);
        $this->assertSame('German', $referenceDe);
        $this->assertSame('French', $referenceFr);
    }

    public function testLoadsCountryTranslations(): void
    {
        $tmpDir = $this->getTempDir();

        (new Filesystem())->dumpFile(
            "$tmpDir/contao/languages/en/countries.php",
            '<?php '
            .'$GLOBALS["TL_LANG"]["CNT"]["us"] = "USA";'
        );

        (new Filesystem())->dumpFile(
            "$tmpDir/contao/languages/de/countries.php",
            '<?php '
            .'$GLOBALS["TL_LANG"]["CNT"]["us"] = "Ami";'
            .'$GLOBALS["TL_LANG"]["CNT"]["de"] = "Deu";'
        );

        (new Filesystem())->dumpFile(
            "$tmpDir/contao/languages/fr/countries.php",
            '<?php '
            .'$GLOBALS["TL_LANG"]["CNT"]["us"] = "Amé";'
            .'$GLOBALS["TL_LANG"]["CNT"]["fr"] = "Fra";'
        );

        System::setContainer($this->getContainerWithLocalesAndCountries($tmpDir));

        $referenceUs = &$GLOBALS['TL_LANG']['CNT']['us'];
        $referenceDe = &$GLOBALS['TL_LANG']['CNT']['de'];
        $referenceFr = &$GLOBALS['TL_LANG']['CNT']['fr'];

        System::loadLanguageFile('countries', 'en');

        $this->assertSame('USA', $GLOBALS['TL_LANG']['CNT']['us']);
        $this->assertSame('Germany', $GLOBALS['TL_LANG']['CNT']['de']);
        $this->assertSame('France', $GLOBALS['TL_LANG']['CNT']['fr']);
        $this->assertSame('USA', $referenceUs);
        $this->assertSame('Germany', $referenceDe);
        $this->assertSame('France', $referenceFr);

        System::loadLanguageFile('countries', 'de');

        $this->assertSame('Ami', $GLOBALS['TL_LANG']['CNT']['us']);
        $this->assertSame('Deu', $GLOBALS['TL_LANG']['CNT']['de']);
        $this->assertSame('Frankreich', $GLOBALS['TL_LANG']['CNT']['fr']);
        $this->assertSame('Ami', $referenceUs);
        $this->assertSame('Deu', $referenceDe);
        $this->assertSame('Frankreich', $referenceFr);

        System::loadLanguageFile('countries', 'fr');

        $this->assertSame('Amé', $GLOBALS['TL_LANG']['CNT']['us']);
        $this->assertSame('Allemagne', $GLOBALS['TL_LANG']['CNT']['de']);
        $this->assertSame('Fra', $GLOBALS['TL_LANG']['CNT']['fr']);
        $this->assertSame('Amé', $referenceUs);
        $this->assertSame('Allemagne', $referenceDe);
        $this->assertSame('Fra', $referenceFr);

        System::loadLanguageFile('countries', 'en');

        $this->assertSame('USA', $GLOBALS['TL_LANG']['CNT']['us']);
        $this->assertSame('Germany', $GLOBALS['TL_LANG']['CNT']['de']);
        $this->assertSame('France', $GLOBALS['TL_LANG']['CNT']['fr']);
        $this->assertSame('USA', $referenceUs);
        $this->assertSame('Germany', $referenceDe);
        $this->assertSame('France', $referenceFr);
    }

    private function getContainerWithLocalesAndCountries(string $tmpDir): ContainerBuilder
    {
        $container = $this->getContainerWithContaoConfiguration($tmpDir);
        $container->setParameter('contao.resources_paths', ["$tmpDir/contao"]);

        $container->set('contao.framework', $this->mockContaoFramework([
            System::class => new Adapter(System::class),
        ]));

        $innerTranslator = new class() implements TranslatorInterface, TranslatorBagInterface {
            public function getCatalogue($locale = null): MessageCatalogue
            {
                return new MessageCatalogue($locale);
            }

            public function trans($id, array $parameters = [], $domain = null, $locale = null)
            {
                return $id;
            }
        };

        $translator = new Translator(
            $innerTranslator,
            $container->get('contao.framework'),
            new ResourceFinder(["$tmpDir/contao"])
        );

        $container->set(
            'contao.intl.locales',
            new Locales(
                $translator,
                new RequestStack(),
                $container->get('contao.framework'),
                ['de', 'en', 'fr'],
                ['de', 'en', 'fr'],
                [],
                [],
                'de'
            )
        );

        $container->set(
            'contao.intl.countries',
            new Countries(
                $translator,
                new RequestStack(),
                $container->get('contao.framework'),
                ['DE', 'US', 'FR'],
                [],
                'de'
            )
        );

        return $container;
    }
}
