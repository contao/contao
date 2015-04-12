<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\RefererId;
use Contao\CoreBundle\RefererId\TokenGenerator;

/**
 * Tests the TokenGenerator class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class TokenGeneratorTest extends \PHPUnit_Framework_TestCase
{
    const ENTROPY = 1000;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $random;

    /**
     * @var TokenGenerator
     */
    private $generator;

    /**
     * A non alpha-numeric byte string
     * @var string
     */
    private static $bytes;

    public static function setUpBeforeClass()
    {
        self::$bytes = base64_decode('aMf+Tct/RLn2WQ==');
    }

    protected function setUp()
    {
        $this->random = $this->getMock('Symfony\Component\Security\Core\Util\SecureRandomInterface');
        $this->generator = new TokenGenerator($this->random, self::ENTROPY);
    }

    protected function tearDown()
    {
        $this->random = null;
        $this->generator = null;
    }

    public function testGenerateTokenHasLengthOf8()
    {
        $this->random->expects($this->once())
            ->method('nextBytes')
            ->with(self::ENTROPY/8)
            ->will($this->returnValue(self::$bytes));

        $token = $this->generator->generateToken();

        $this->assertEquals(8, strlen($token));
    }
}