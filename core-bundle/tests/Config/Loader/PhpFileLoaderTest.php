<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Config\Loader;

use Contao\CoreBundle\Config\Loader\PhpFileLoader;
use Contao\CoreBundle\Tests\TestCase;

/**
 * Tests the PhpFileLoader class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Yanick Witschi <https://github.com/Toflar>
 * @author Leo Feyer <https://github.com/leofeyer>
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
    public function testCanBeInstantiated()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Config\Loader\PhpFileLoader', $this->loader);
    }

    /**
     * Tests that only PHP files are supported.
     */
    public function testSupportsPhpFiles()
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
     * Tests loading a PHP file.
     */
    public function testLoadsPhpFiles()
    {
        $expects = <<<'EOF'

$GLOBALS['TL_TEST'] = true;

EOF;

        $this->assertSame(
            $expects,
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

        $this->assertSame(
            $content,
            $this->loader->load($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao/dca/tl_test.php')
        );
    }

    /**
     * Tests that custom namespaces are added.
     */
    public function testAddsCustomNamespaces()
    {
        $expects = <<<'EOF'

namespace Foo\Bar {
$GLOBALS['TL_DCA']['tl_test']['config']['dataContainer'] = 'DC_Table';
}

EOF;

        $this->assertSame(
            $expects,
            $this->loader->load(
                $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao/dca/tl_test_with_namespace.php',
                'namespaced'
            )
        );

        $expects = <<<'EOF'

namespace  {
$GLOBALS['TL_TEST'] = true;
}

EOF;

        $this->assertSame(
            $expects,
            $this->loader->load(
                $this->getRootDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/tl_test.php',
                'namespaced'
            )
        );
    }

    /**
     * Tests that a declare(strict_types=1) statement is stripped.
     *
     * @param string $file
     *
     * @dataProvider loadWithDeclareStatementsStrictType
     */
    public function testStripsDeclareStrictTypes($file)
    {
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

        $this->assertSame(
            $content,
            $this->loader->load($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao/dca/'.$file.'.php')
        );
    }

    /**
     * Tests that a declare(strict_types=1) statement in a comment is ignored.
     *
     * @dataProvider loadWithDeclareStatementsStrictType
     */
    public function testIgnoresDeclareStatementsInComments()
    {
        $content = <<<'EOF'

/**
 * I am a declare(strict_types=1) comment
 */



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

        $this->assertSame(
            $content,
            $this->loader->load($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao/dca/tl_test_with_declare3.php')
        );
    }

    /**
     * Provides the data for the declare(strict_types=1) tests.
     *
     * @return array
     */
    public function loadWithDeclareStatementsStrictType()
    {
        return [
            ['tl_test_with_declare1'],
            ['tl_test_with_declare2'],
        ];
    }

    /**
     * Tests that other definitions than strict_types are preserved.
     *
     * @param string $file
     *
     * @dataProvider loadWithDeclareStatementsMultipleDefined
     */
    public function testPreservesOtherDeclareDefinitions($file)
    {
        $content = <<<'EOF'

declare(ticks=1);

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

        $this->assertSame(
            $content,
            $this->loader->load($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao/dca/'.$file.'.php')
        );
    }

    /**
     * Provides the data for the declare(strict_types=1,ticks=1) tests.
     *
     * @return array
     */
    public function loadWithDeclareStatementsMultipleDefined()
    {
        return [
            ['tl_test_with_declare4'],
            ['tl_test_with_declare5'],
            ['tl_test_with_declare6'],
        ];
    }
}
