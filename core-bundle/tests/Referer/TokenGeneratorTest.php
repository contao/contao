<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Referer;

use Contao\CoreBundle\Referer\TokenGenerator;

/**
 * Tests the TokenGenerator class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class TokenGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests whether the generated token is eight characters long.
     */
    public function testGeneratedTokenHasLengthOfEight()
    {
        $generator = new TokenGenerator(1000);
        $this->assertEquals(8, strlen($generator->generateToken()));
    }
}
