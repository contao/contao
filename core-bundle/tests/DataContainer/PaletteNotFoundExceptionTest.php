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

use Contao\CoreBundle\DataContainer\PaletteNotFoundException;
use PHPUnit\Framework\TestCase;

class PaletteNotFoundExceptionTest extends TestCase
{
    public function testExceptionInheritance(): void
    {
        $exception = new PaletteNotFoundException();

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(\Contao\CoreBundle\Exception\PaletteNotFoundException::class, $exception);
    }
}
