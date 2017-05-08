<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
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
    public function testGeneratedTokenHasLengthOfEight()
    {
        $generator = new TokenGenerator(1000);

        $this->assertSame(8, strlen($generator->generateToken()));
    }
}
