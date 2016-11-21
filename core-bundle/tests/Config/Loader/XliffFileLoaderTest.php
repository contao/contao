<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Config\Loader;

use Contao\CoreBundle\Config\Loader\XliffFileLoader;
use Contao\CoreBundle\Test\TestCase;

/**
 * Tests the XliffFileLoader class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class XliffFileLoaderTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf(
            'Contao\CoreBundle\Config\Loader\XliffFileLoader',
            new XliffFileLoader($this->getRootDir().'/app')
        );
    }

    /**
     * Tests the supports() method.
     */
    public function testSupports()
    {
        $loader = new XliffFileLoader($this->getRootDir().'/app');

        $this->assertTrue(
            $loader->supports(
                $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/default.xlf'
            )
        );

        $this->assertFalse(
            $loader->supports(
                $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/tl_test.php'
            )
        );
    }

    /**
     * Tests loading a file into a string.
     */
    public function testLoadIntoString()
    {
        $loader = new XliffFileLoader($this->getRootDir().'/app', false);

        $source = <<<'TXT'

// vendor/contao/test-bundle/Resources/contao/languages/en/default.xlf
$GLOBALS['TL_LANG']['MSC']['first'] = 'This is the first source';
$GLOBALS['TL_LANG']['MSC']['second'][0] = 'This is the second source';
$GLOBALS['TL_LANG']['MSC']['third']['with'][1] = 'This is the third source';
$GLOBALS['TL_LANG']['tl_layout']['responsive.css'][1] = 'This is the fourth source';
$GLOBALS['TL_LANG']['MSC']['fifth'] = "This is the\nfifth source";

TXT;

        $target = <<<'TXT'

// vendor/contao/test-bundle/Resources/contao/languages/en/default.xlf
$GLOBALS['TL_LANG']['MSC']['first'] = 'This is the first target';
$GLOBALS['TL_LANG']['MSC']['second'][0] = 'This is the second target';
$GLOBALS['TL_LANG']['MSC']['third']['with'][1] = 'This is the third target';
$GLOBALS['TL_LANG']['tl_layout']['responsive.css'][1] = 'This is the fourth target';
$GLOBALS['TL_LANG']['MSC']['fifth'] = "This is the\nfifth target";

TXT;

        $this->assertEquals(
            $source,
            $loader->load(
                $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/default.xlf',
                'en'
            )
        );

        $this->assertEquals(
            $target,
            $loader->load(
                $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/default.xlf',
                'de'
            )
        );
    }

    /**
     * Tests loading a file into the global variables.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testLoadIntoGlobal()
    {
        $loader = new XliffFileLoader($this->getRootDir().'/app', true);

        $loader->load(
            $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/default.xlf',
            'en'
        );

        $this->assertEquals('This is the first source', $GLOBALS['TL_LANG']['MSC']['first']);
        $this->assertEquals('This is the second source', $GLOBALS['TL_LANG']['MSC']['second'][0]);
        $this->assertEquals('This is the third source', $GLOBALS['TL_LANG']['MSC']['third']['with'][1]);

        $loader->load(
            $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/default.xlf',
            'de'
        );

        $this->assertEquals('This is the first target', $GLOBALS['TL_LANG']['MSC']['first']);
        $this->assertEquals('This is the second target', $GLOBALS['TL_LANG']['MSC']['second'][0]);
        $this->assertEquals('This is the third target', $GLOBALS['TL_LANG']['MSC']['third']['with'][1]);
    }

    /**
     * Tests loading a file with too many nesting levels.
     *
     * @expectedException \OutOfBoundsException
     */
    public function testTooManyLevels()
    {
        $loader = new XliffFileLoader($this->getRootDir().'/app', false);

        $loader->load(
            $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/error.xlf',
            'en'
        );
    }
}
