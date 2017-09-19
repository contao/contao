<?php

declare(strict_types=1);

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
 */
class TokenGeneratorTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated(): void
    {
        $generator = new TokenGenerator(1000);

        $this->assertInstanceOf('Contao\CoreBundle\Referer\TokenGenerator', $generator);
    }

    /**
     * Tests whether the generated token is eight characters long.
     */
    public function testGeneratesAnEightCharacterToken(): void
    {
        $generator = new TokenGenerator(1000);

        $this->assertSame(8, strlen($generator->generateToken()));
    }
}
