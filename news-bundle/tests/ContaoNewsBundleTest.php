<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
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
