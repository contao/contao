<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Referer;

use Contao\CoreBundle\Referer\TokenGenerator;
use PHPUnit\Framework\TestCase;

class TokenGeneratorTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $generator = new TokenGenerator(1000);

        $this->assertInstanceOf('Contao\CoreBundle\Referer\TokenGenerator', $generator);
    }

    public function testGeneratesAnEightCharacterToken(): void
    {
        $generator = new TokenGenerator(1000);

        $this->assertSame(8, \strlen($generator->generateToken()));
    }
}
