<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\InsertTag\Resolver;

use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\ResolvedParameters;
use Contao\CoreBundle\InsertTag\Resolver\AssetInsertTag;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Asset\Packages;

class AssetInsertTagTest extends TestCase
{
    public function testReplacesInsertTagsWithPackageName(): void
    {
        $packages = $this->createMock(Packages::class);
        $packages
            ->expects($this->once())
            ->method('getUrl')
            ->with('foo/bar', 'package')
            ->willReturn('/foo/bar')
        ;

        $listener = new AssetInsertTag($packages);
        $insertTag = new ResolvedInsertTag('asset', new ResolvedParameters(['foo/bar', 'package']), []);
        $result = $listener($insertTag);

        $this->assertSame('/foo/bar', $result->getValue());
        $this->assertSame(OutputType::url, $result->getOutputType());
    }

    public function testReplacesInsertTagsWithoutPackageName(): void
    {
        $packages = $this->createMock(Packages::class);
        $packages
            ->expects($this->once())
            ->method('getUrl')
            ->with('foo/bar', null)
            ->willReturn('/foo/bar')
        ;

        $listener = new AssetInsertTag($packages);
        $insertTag = new ResolvedInsertTag('asset', new ResolvedParameters(['foo/bar']), []);
        $result = $listener($insertTag);

        $this->assertSame('/foo/bar', $result->getValue());
        $this->assertSame(OutputType::url, $result->getOutputType());
    }
}
