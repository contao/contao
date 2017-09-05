<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\Tests;

use Contao\CalendarBundle\ContaoCalendarBundle;
use PHPUnit\Framework\TestCase;

/**
 * Tests the ContaoCalendarBundle class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoCalendarBundleTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $bundle = new ContaoCalendarBundle();

        $this->assertInstanceOf('Contao\CalendarBundle\ContaoCalendarBundle', $bundle);
    }
}
