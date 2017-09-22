<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Tests;

use Contao\ManagerBundle\ContaoManagerBundle;
use PHPUnit\Framework\TestCase;

class ContaoManagerBundleTest extends TestCase
{
    public function testInstantiation(): void
    {
        $this->assertInstanceOf('Contao\ManagerBundle\ContaoManagerBundle', new ContaoManagerBundle());
    }
}
