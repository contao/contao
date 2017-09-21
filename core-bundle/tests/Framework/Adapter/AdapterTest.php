<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Framework\Adapter;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Tests\Fixtures\Adapter\LegacyClass;
use PHPUnit\Framework\TestCase;

class AdapterTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $adapter = new Adapter('Dummy');

        $this->assertInstanceOf('Contao\CoreBundle\Framework\Adapter', $adapter);
    }

    public function testImplementsTheMagicCallMethod(): void
    {
        /** @var LegacyClass $adapter */
        $adapter = new Adapter(LegacyClass::class);

        $this->assertSame(['staticMethod', 1, 2], $adapter->staticMethod(1, 2));
    }

    public function testFailsIfAMethodDoesNotExist(): void
    {
        $adapter = new Adapter(LegacyClass::class);

        $this->expectException('TypeError');

        /** @noinspection PhpUndefinedMethodInspection */
        $adapter->missingMethod();
    }
}
