<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Cache;

use Contao\CoreBundle\Cache\ApplicationCacheState;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class ApplicationCacheStateTest extends TestCase
{
    public function testIsDirty(): void
    {
        $cacheState = new ApplicationCacheState(new ArrayAdapter());

        $this->assertFalse($cacheState->isDirty());

        $cacheState->markDirty();

        $this->assertTrue($cacheState->isDirty());
    }
}
