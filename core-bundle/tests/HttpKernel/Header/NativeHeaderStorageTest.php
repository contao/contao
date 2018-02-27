<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\HttpKernel\Header;

use Contao\CoreBundle\HttpKernel\Header\NativeHeaderStorage;
use Contao\CoreBundle\Tests\TestCase;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class NativeHeaderStorageTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $storage = new NativeHeaderStorage();

        $this->assertInstanceOf('Contao\CoreBundle\HttpKernel\Header\NativeHeaderStorage', $storage);
        $this->assertInstanceOf('Contao\CoreBundle\HttpKernel\Header\HeaderStorageInterface', $storage);
    }
}
