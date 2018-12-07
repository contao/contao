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

use Contao\CoreBundle\Config\Loader\PhpFileIncluder;
use Contao\CoreBundle\Tests\TestCase;

class PhpFileIncluderTest extends TestCase
{
    /**
     * @var PhpFileIncluder
     */
    private $loader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loader = new PhpFileIncluder();
    }

    public function testSupportsPhpFiles(): void
    {
        $this->assertTrue(
            $this->loader->supports(
                $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/default.php'
            )
        );

        $this->assertFalse(
            $this->loader->supports(
                $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/default.xlf'
            )
        );
    }

    public function testIncludesPhpFiles(): void
    {
        $this->loader->load(
            $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/default.php'
        );

        $this->assertTrue(isset($GLOBALS['TL_LANG']['MSC']['test']));
        $this->assertSame('Test', $GLOBALS['TL_LANG']['MSC']['test']);

        unset($GLOBALS['TL_LANG']);
    }
}
