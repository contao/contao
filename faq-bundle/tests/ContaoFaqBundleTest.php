<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\FaqBundle\Test;

use Contao\FaqBundle\ContaoFaqBundle;

/**
 * Tests the ContaoFaqBundle class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoFaqBundleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $bundle = new ContaoFaqBundle();

        $this->assertInstanceOf('Contao\FaqBundle\ContaoFaqBundle', $bundle);
    }
}
