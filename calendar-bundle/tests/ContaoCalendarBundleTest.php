<?php

declare(strict_types=1);

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

class ContaoCalendarBundleTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $bundle = new ContaoCalendarBundle();

        $this->assertInstanceOf('Contao\CalendarBundle\ContaoCalendarBundle', $bundle);
    }
}
