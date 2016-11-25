<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Config\Loader;

use Contao\CoreBundle\Config\Loader\PhpFileLoader;
use Contao\CoreBundle\Test\TestCase;

/**
 * Tests the PhpFileLoader class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class PhpFileLoaderTest extends TestCase
{
    /**
     * @var PhpFileLoader
     */
    private $loader;

    /**
     * Creates the PhpFileLoader object.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loader = new PhpFileLoader();
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Config\Loader\PhpFileLoader', $this->loader);
    }

    /**
     * Tests the supports() method.
     */
    public function testSupports()
    {
        $this->assertTrue(
            $this->loader->supports(
                $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao/config/config.php'
            )
        );

        $this->assertFalse(
            $this->loader->supports(
                $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/default.xlf'
            )
        );
    }

    /**
     * Tests the load() method.
     */
    public function testLoad()
    {
        $this->assertEquals(
            "\n\n\$GLOBALS['TL_TEST'] = true;\n",
            $this->loader->load($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao/config/config.php')
        );

        $content = <<<'EOF'


$GLOBALS['TL_DCA']['tl_test'] = [
    'config' => [
        'dataContainer' => 'DC_Table',
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
    ],
];

EOF;

        $this->assertEquals(
            $content,
            $this->loader->load($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao/dca/tl_test.php')
        );

        $this->assertEquals(
            "\n\n\$GLOBALS['TL_TEST'] = true;\n",
            $this->loader->load(
                $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/tl_test.php'
            )
        );
    }
}
