<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\Tests;

use Contao\InstallationBundle\ContaoInstallationBundle;
use PHPUnit\Framework\TestCase;

/**
 * Tests the ContaoInstallationBundle class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoInstallationBundleTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $bundle = new ContaoInstallationBundle();

        $this->assertInstanceOf('Contao\InstallationBundle\ContaoInstallationBundle', $bundle);
    }
}
