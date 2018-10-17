<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Framework\Adapter;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Tests\Fixtures\Adapter\LegacyClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Adapter class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class AdapterTest extends TestCase
{
    /**
     * Tests the __call method.
     */
    public function testImplementsTheMagicCallMethod()
    {
        /** @var LegacyClass $adapter */
        $adapter = new Adapter(LegacyClass::class);

        $this->assertSame(['staticMethod', 1, 2], $adapter->staticMethod(1, 2));
    }

    /**
     * Tests the __call method of a non-existent function.
     */
    public function testFailsIfAMethodDoesNotExist()
    {
        $adapter = new Adapter(LegacyClass::class);

        $this->expectException('PHPUnit_Framework_Error');

        /* @noinspection PhpUndefinedMethodInspection */
        $adapter->missingMethod();
    }
}
