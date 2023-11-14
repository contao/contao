<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Config\Loader;

use Contao\CoreBundle\Config\Loader\XliffFileLoader;
use Contao\CoreBundle\Tests\TestCase;

class XliffFileLoaderTest extends TestCase
{
    private XliffFileLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loader = new XliffFileLoader($this->getFixturesDir());
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG']);

        parent::tearDown();
    }

    public function testSupportsXlfFiles(): void
    {
        $this->assertTrue(
            $this->loader->supports(
                $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/default.xlf'
            )
        );

        $this->assertFalse(
            $this->loader->supports(
                $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/tl_test.php'
            )
        );
    }

    public function testLoadsXlfFilesIntoAString(): void
    {
        $source = <<<'TXT'

            // vendor/contao/test-bundle/Resources/contao/languages/en/default.xlf
            $GLOBALS['TL_LANG']['MSC']['first'] = 'This is the first source';
            $GLOBALS['TL_LANG']['MSC']['second'][0] = 'This is the second source';
            $GLOBALS['TL_LANG']['MSC']['third']['with'][1] = 'This is the third source';
            $GLOBALS['TL_LANG']['tl_layout']['responsive.css'][1] = 'This is the fourth source';
            $GLOBALS['TL_LANG']['MSC']['fifth'] = "This is the\nfifth source";
            $GLOBALS['TL_LANG']['MSC']['only_source'] = 'This is the source';
            $GLOBALS['TL_LANG']['MSC']['in_group_1'] = 'This is in group 1 source';
            $GLOBALS['TL_LANG']['MSC']['in_group_2'] = 'This is in group 2 source';
            $GLOBALS['TL_LANG']['MSC']['second_file'] = 'This is the target';

            TXT;

        $target = <<<'TXT'

            // vendor/contao/test-bundle/Resources/contao/languages/en/default.xlf
            $GLOBALS['TL_LANG']['MSC']['first'] = 'This is the first target';
            $GLOBALS['TL_LANG']['MSC']['second'][0] = 'This is the second target';
            $GLOBALS['TL_LANG']['MSC']['third']['with'][1] = 'This is the third target';
            $GLOBALS['TL_LANG']['tl_layout']['responsive.css'][1] = 'This is the fourth target';
            $GLOBALS['TL_LANG']['MSC']['fifth'] = "This is the\nfifth target";
            $GLOBALS['TL_LANG']['MSC']['only_target'] = 'This is the target';
            $GLOBALS['TL_LANG']['MSC']['in_group_1'] = 'This is in group 1 target';
            $GLOBALS['TL_LANG']['MSC']['in_group_2'] = 'This is in group 2 target';
            $GLOBALS['TL_LANG']['MSC']['second_file'] = 'This is the source';

            TXT;

        $this->assertSame(
            $source,
            $this->loader->load(
                $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/default.xlf',
                'en'
            )
        );

        $this->assertSame(
            $target,
            $this->loader->load(
                $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/default.xlf',
                'de'
            )
        );
    }

    public function testLoadsXlfFilesIntoTheGlobalVariables(): void
    {
        $loader = new XliffFileLoader($this->getFixturesDir().'/app', true);
        $loader->load(
            $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/default.xlf',
            'en'
        );

        $this->assertSame('This is the first source', $GLOBALS['TL_LANG']['MSC']['first']);
        $this->assertSame('This is the second source', $GLOBALS['TL_LANG']['MSC']['second'][0]);
        $this->assertSame('This is the third source', $GLOBALS['TL_LANG']['MSC']['third']['with'][1]);

        $loader->load(
            $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/default.xlf',
            'de'
        );

        $this->assertSame('This is the first target', $GLOBALS['TL_LANG']['MSC']['first']);
        $this->assertSame('This is the second target', $GLOBALS['TL_LANG']['MSC']['second'][0]);
        $this->assertSame('This is the third target', $GLOBALS['TL_LANG']['MSC']['third']['with'][1]);
    }

    public function testOverridesKeysInLanguageArray(): void
    {
        $GLOBALS['TL_LANG']['MSC']['third'] = 'is-a-string';

        $loader = new XliffFileLoader($this->getFixturesDir().'/app', true);
        $loader->load($this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/default.xlf', 'en');

        $this->assertIsArray($GLOBALS['TL_LANG']['MSC']['third']);
        $this->assertSame('This is the third source', $GLOBALS['TL_LANG']['MSC']['third']['with'][1]);
    }
}
