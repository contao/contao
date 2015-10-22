<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Referer;

use Contao\CoreBundle\Referer\TokenGenerator;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\Security\Core\Util\SecureRandomInterface;

/**
 * Tests the TokenGenerator class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class TokenGeneratorTest extends TestCase
{
    const ENTROPY = 1000;

    /**
     * @var SecureRandomInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $random;

    /**
     * @var TokenGenerator
     */
    private $generator;

    /**
     * @var string
     */
    private static $bytes;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        self::$bytes = base64_decode('aMf+Tct/RLn2WQ==');
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->random = $this->getMock('Symfony\Component\Security\Core\Util\SecureRandomInterface');
        $this->generator = new TokenGenerator($this->random, self::ENTROPY);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->random = null;
        $this->generator = null;
    }

    /**
     * Tests whether the generated token is eight characters long.
     */
    public function testGeneratedTokenHasLengthOfEight()
    {
        $this->random->expects($this->once())
            ->method('nextBytes')
            ->with(self::ENTROPY / 8)
            ->will($this->returnValue(self::$bytes))
        ;

        $this->assertEquals(8, strlen($this->generator->generateToken()));
    }
}
