<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\Controller;
use Contao\System;
use PHPUnit\Framework\TestCase;

class ControllerTest extends TestCase
{
    public function testReturnsTheTimeZones(): void
    {
        $timeZones = System::getTimeZones();

        $this->assertCount(9, $timeZones['General']);
        $this->assertCount(51, $timeZones['Africa']);
        $this->assertCount(140, $timeZones['America']);
        $this->assertCount(10, $timeZones['Antarctica']);
        $this->assertCount(83, $timeZones['Asia']);
        $this->assertCount(11, $timeZones['Atlantic']);
        $this->assertCount(22, $timeZones['Australia']);
        $this->assertCount(4, $timeZones['Brazil']);
        $this->assertCount(9, $timeZones['Canada']);
        $this->assertCount(2, $timeZones['Chile']);
        $this->assertCount(53, $timeZones['Europe']);
        $this->assertCount(11, $timeZones['Indian']);
        $this->assertCount(4, $timeZones['Brazil']);
        $this->assertCount(3, $timeZones['Mexico']);
        $this->assertCount(40, $timeZones['Pacific']);
        $this->assertCount(13, $timeZones['United States']);
    }

    public function testGeneratesTheMargin(): void
    {
        $margins = [
            'top' => '40px',
            'right' => '10%',
            'bottom' => '-2px',
            'left' => '-50%',
            'unit' => '',
        ];

        $this->assertSame('margin:40px 10% -2px -50%;', Controller::generateMargin($margins));
    }
}
