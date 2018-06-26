<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests;

use Contao\NewsBundle\ContaoNewsBundle;
use PHPUnit\Framework\TestCase;

class ContaoNewsBundleTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $bundle = new ContaoNewsBundle();

        $this->assertInstanceOf('Contao\NewsBundle\ContaoNewsBundle', $bundle);
    }
}
