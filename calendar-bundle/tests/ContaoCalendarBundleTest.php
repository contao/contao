<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\Test;

use Contao\CalendarBundle\ContaoCalendarBundle;

/**
 * Tests the ContaoCalendarBundle class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoCalendarBundleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $bundle = new ContaoCalendarBundle();

        $this->assertInstanceOf('Contao\CalendarBundle\ContaoCalendarBundle', $bundle);
    }
}
