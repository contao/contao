<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Config\Loader;

use Contao\CoreBundle\Config\Loader\PhpFileIncluder;
use Contao\CoreBundle\Tests\TestCase;

/**
 * Tests the PhpFileIncluder class.
 *
 * @author Mike vom Scheidt <https://github.com/mvscheidt>
 */
class PhpFileIncluderTest extends TestCase
{
    /**
     * @var PhpFileIncluder
     */
    private $loader;

    protected function setUp()
    {
        parent::setUp();

        $this->loader = new PhpFileIncluder();
    }

    public function testCanBeInstantiated()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Config\Loader\PhpFileIncluder', $this->loader);
    }

    public function testSupportsPhpFiles()
    {
        $this->assertTrue(
            $this->loader->supports(
                $this->getFixturesDir() . '/vendor/contao/test-bundle/Resources/contao/languages/en/tl_test.php'
            )
        );
    }

    public function testDoesNotSupportOtherFiletypes()
    {
        $this->assertFalse(
            $this->loader->supports(
                $this->getFixturesDir() . '/vendor/contao/test-bundle/Resources/contao/languages/en/default.xlf'
            )
        );
    }

    public function testIncludesPhpFiles()
    {
        //the test files "dies" if the TL_ROOT is not defined
        define('TL_ROOT', '');

        $this->loader->load(
            $this->getFixturesDir() . '/vendor/contao/test-bundle/Resources/contao/languages/en/tl_test.php'
        );

        $this->assertArrayHasKey('TL_TEST', $GLOBALS);
        $this->assertEquals(true, $GLOBALS['TL_TEST']);
    }
}
