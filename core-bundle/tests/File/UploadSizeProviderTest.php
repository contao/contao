<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\File;

use Contao\CoreBundle\File\UploadSizeProvider;
use PHPUnit\Framework\TestCase;

final class UploadSizeProviderTest extends TestCase
{
    public function testReturnsTheLowerPhpMaximum(): void
    {
        $provider = new UploadSizeProvider(2345, 1234);

        $this->assertSame(1234, $provider->getMaximumUploadSize());
    }

    public function testReturnsTheLowerContaoMaximum(): void
    {
        $provider = new UploadSizeProvider(1234, 2345);

        $this->assertSame(1234, $provider->getMaximumUploadSize());
    }

    public function testReturnsTheMaximumInMegabytes(): void
    {
        $provider = new UploadSizeProvider(intdiv(3 * 1024 * 1024, 2), 2 * 1024 * 1024);

        $this->assertSame(2.0, $provider->getMaximumUploadSizeInMegabytes());
    }
}
