<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\Tests;

use Contao\FaqBundle\ContaoFaqBundle;
use PHPUnit\Framework\TestCase;

/**
 * Tests the ContaoFaqBundle class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoFaqBundleTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $bundle = new ContaoFaqBundle();

        $this->assertInstanceOf('Contao\FaqBundle\ContaoFaqBundle', $bundle);
    }
}
