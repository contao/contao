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

use Contao\CoreBundle\Tests\TestCase;
use Contao\System;
use Symfony\Component\Filesystem\Filesystem;

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
}
