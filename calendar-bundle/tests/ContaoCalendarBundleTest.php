<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
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
