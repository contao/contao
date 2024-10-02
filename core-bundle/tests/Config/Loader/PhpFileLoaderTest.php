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

use Contao\CoreBundle\Config\Loader\PhpFileLoader;
use Contao\CoreBundle\Tests\TestCase;

class PhpFileLoaderTest extends TestCase
{
    private PhpFileLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loader = new PhpFileLoader();
    }

    public function testSupportsPhpFiles(): void
    {
        $this->assertTrue(
            $this->loader->supports(
                $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/config/config.php',
            ),
        );

        $this->assertFalse(
            $this->loader->supports(
                $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/default.xlf',
            ),
        );
    }

    public function testLoadsPhpFiles(): void
    {
        $expects = <<<'EOF'

            $GLOBALS['TL_TEST'] = \true;

            EOF;

        $this->assertSame(
            $expects,
            $this->loader->load($this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/config/config.php'),
        );

        $content = <<<'EOF'

            $GLOBALS['TL_DCA']['tl_test'] = ['config' => ['dataContainer' => \Contao\DC_Table::class, 'sql' => ['keys' => ['id' => 'primary']]], 'fields' => ['id' => ['sql' => "int(10) unsigned NOT NULL auto_increment"]]];

            EOF;

        $this->assertSame(
            $content,
            $this->loader->load($this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/dca/tl_test.php'),
        );
    }

    public function testAddsCustomNamespaces(): void
    {
        $expects = <<<'EOF'

            namespace Foo\Bar {
            $GLOBALS['TL_DCA']['tl_test_with_namespace1']['config']['dataContainer'] = \Contao\DC_Table::class;
            }

            EOF;

        $this->assertSame(
            $expects,
            $this->loader->load(
                $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/dca/tl_test_with_namespace1.php',
                'namespaced',
            ),
        );

        $expects = <<<'EOF'

            namespace {
            $GLOBALS['TL_DCA']['tl_test_with_namespace2']['config']['dataContainer'] = \Contao\DC_Table::class;
            }

            EOF;

        $this->assertSame(
            $expects,
            $this->loader->load(
                $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/dca/tl_test_with_namespace2.php',
                'namespaced',
            ),
        );

        $expects = <<<'EOF'

            namespace {
            $GLOBALS['TL_TEST'] = \true;
            }

            EOF;

        $this->assertSame(
            $expects,
            $this->loader->load(
                $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/tl_test.php',
                'namespaced',
            ),
        );
    }

    public function testAddsCheckAroundClasses(): void
    {
        $expects = <<<'EOF'

            namespace {
            $GLOBALS['TL_DCA']['tl_test_with_class'] = ['config' => ['dataContainer' => \Contao\DC_Table::class], 'fields' => ['id' => ['sql' => 'int(10) unsigned NOT NULL auto_increment']]];
            if (!\class_exists(tl_test_with_class::class)) {
                class tl_test_with_class
                {
                    public function checkPermission(\Contao\DataContainer $dc)
                    {
                    }
                }
            }
            }

            EOF;

        $this->assertSame(
            $expects,
            $this->loader->load(
                $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/dca/tl_test_with_class.php',
                'namespaced',
            ),
        );

        $expects = <<<'EOF'

            namespace {
            $GLOBALS['TL_DCA']['tl_test_with_class'] = ['config' => ['dataContainer' => \Contao\DC_Table::class], 'fields' => ['id' => ['sql' => 'int(10) unsigned NOT NULL auto_increment']]];
            if (!\class_exists(tl_test_with_classes1::class)) {
                class tl_test_with_classes1
                {
                    public function checkPermission(\Contao\DataContainer $dc)
                    {
                    }
                }
            }
            if (!\class_exists(tl_test_with_classes2::class)) {
                class tl_test_with_classes2
                {
                    public function checkPermission(\Contao\DataContainer $dc)
                    {
                    }
                }
            }
            }

            EOF;

        $this->assertSame(
            $expects,
            $this->loader->load(
                $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/dca/tl_test_with_classes.php',
                'namespaced',
            ),
        );

        $expects = <<<'EOF'

            namespace Foo\Bar {
            $GLOBALS['TL_DCA']['tl_test_with_class'] = ['config' => ['dataContainer' => \Contao\DC_Table::class], 'fields' => ['id' => ['sql' => 'int(10) unsigned NOT NULL auto_increment']]];
            if (!\class_exists(tl_test_with_class_namespaced::class)) {
                class tl_test_with_class_namespaced
                {
                    public function checkPermission(\Contao\DataContainer $dc)
                    {
                    }
                }
            }
            }

            EOF;

        $this->assertSame(
            $expects,
            $this->loader->load(
                $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/dca/tl_test_with_class_namespaced.php',
                'namespaced',
            ),
        );
    }

    /**
     * @dataProvider loadWithDeclareStatementsStrictType
     */
    public function testStripsDeclareStrictTypes(string $file): void
    {
        $content = <<<EOF

            \$GLOBALS['TL_DCA']['$file'] = ['config' => ['dataContainer' => \\Contao\\DC_Table::class, 'sql' => ['keys' => ['id' => 'primary']]], 'fields' => ['id' => ['sql' => "int(10) unsigned NOT NULL auto_increment"]]];

            EOF;

        $this->assertSame(
            $content,
            $this->loader->load(
                $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/dca/'.$file.'.php',
            ),
        );
    }

    public function testIgnoresDeclareStatementsInComments(): void
    {
        $content = <<<'EOF'

            $GLOBALS['TL_DCA']['tl_test_with_declare3'] = ['config' => ['dataContainer' => \Contao\DC_Table::class, 'sql' => ['keys' => ['id' => 'primary']]], 'fields' => ['id' => ['sql' => "int(10) unsigned NOT NULL auto_increment"]]];

            EOF;

        $this->assertSame(
            $content,
            $this->loader->load(
                $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/dca/tl_test_with_declare3.php',
            ),
        );
    }

    public static function loadWithDeclareStatementsStrictType(): iterable
    {
        yield ['tl_test_with_declare1'];
        yield ['tl_test_with_declare2'];
    }

    /**
     * @dataProvider loadWithDeclareStatementsMultipleDefined
     */
    public function testPreservesOtherDeclareDefinitions(string $file): void
    {
        $content = <<<EOF

            declare (ticks=1);
            \$GLOBALS['TL_DCA']['$file'] = ['config' => ['dataContainer' => \\Contao\\DC_Table::class, 'sql' => ['keys' => ['id' => 'primary']]], 'fields' => ['id' => ['sql' => "int(10) unsigned NOT NULL auto_increment"]]];

            EOF;

        $this->assertSame(
            $content,
            $this->loader->load(
                $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/dca/'.$file.'.php',
            ),
        );
    }

    public static function loadWithDeclareStatementsMultipleDefined(): iterable
    {
        yield ['tl_test_with_declare4'];
        yield ['tl_test_with_declare5'];
        yield ['tl_test_with_declare6'];
    }
}
