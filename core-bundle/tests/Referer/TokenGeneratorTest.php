<?php

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

/**
 * Tests the TokenGenerator class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class TokenGeneratorTest extends TestCase
{
    /**
     * Tests whether the generated token is eight characters long.
     */
    public function testGeneratesAnEightCharacterToken()
    {
        $generator = new TokenGenerator(1000);

        $this->assertSame(8, \strlen($generator->generateToken()));
    }
}
