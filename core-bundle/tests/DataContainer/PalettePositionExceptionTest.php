<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DataContainer;

use Contao\CoreBundle\DataContainer\PalettePositionException;
use PHPUnit\Framework\TestCase;

class PalettePositionExceptionTest extends TestCase
{
    public function testExceptionInheritance(): void
    {
        $exception = new PalettePositionException();

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(\Contao\CoreBundle\Exception\PalettePositionException::class, $exception);
    }
}
