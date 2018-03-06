<?php

declare(strict_types=1);

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

class ContaoFaqBundleTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $bundle = new ContaoFaqBundle();

        $this->assertInstanceOf('Contao\FaqBundle\ContaoFaqBundle', $bundle);
    }
}
