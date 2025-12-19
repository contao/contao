<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\IDE;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Ide\NamespaceLookupFileGenerator;
use Contao\CoreBundle\Twig\Ide\NamespaceLookupFileWarmer;
use Symfony\Component\Filesystem\Exception\IOException;

class NamespaceLookupFileWarmerTest extends TestCase
{
    public function testIsMandatory(): void
    {
        $this->assertFalse($this->getNamespaceLookupFileWarmer()->isOptional());
    }

    public function testWritesFileOnWarmUp(): void
    {
        $namespaceLookupFileGenerator = $this->createMock(NamespaceLookupFileGenerator::class);
        $namespaceLookupFileGenerator
            ->expects($this->once())
            ->method('write')
            ->with('/var/build/contao-ide')
        ;

        $this
            ->getNamespaceLookupFileWarmer($namespaceLookupFileGenerator)
            ->warmUp('/var/cache', '/var/build')
        ;
    }

    public function testDoesNotWriteFileInProd(): void
    {
        $namespaceLookupFileGenerator = $this->createMock(NamespaceLookupFileGenerator::class);
        $namespaceLookupFileGenerator
            ->expects($this->never())
            ->method('write')
        ;

        $this
            ->getNamespaceLookupFileWarmer($namespaceLookupFileGenerator, 'prod')
            ->warmUp('/var/cache', '/var/build')
        ;
    }

    public function testDoesNotWriteFileIfNoBuildDirIsSpecified(): void
    {
        $namespaceLookupFileGenerator = $this->createMock(NamespaceLookupFileGenerator::class);
        $namespaceLookupFileGenerator
            ->expects($this->never())
            ->method('write')
        ;

        $this
            ->getNamespaceLookupFileWarmer($namespaceLookupFileGenerator, 'prod')
            ->warmUp('/var/cache')
        ;
    }

    public function testToleratesFailingWrites(): void
    {
        $namespaceLookupFileGenerator = $this->createMock(NamespaceLookupFileGenerator::class);
        $namespaceLookupFileGenerator
            ->expects($this->once())
            ->method('write')
            ->willThrowException(new IOException('Unable to write'))
        ;

        $this
            ->getNamespaceLookupFileWarmer($namespaceLookupFileGenerator)
            ->warmUp('/var/cache', '/var/build')
        ;
    }

    private function getNamespaceLookupFileWarmer(NamespaceLookupFileGenerator|null $namespaceLookupFileGenerator = null, string $environment = 'dev'): NamespaceLookupFileWarmer
    {
        return new NamespaceLookupFileWarmer(
            $namespaceLookupFileGenerator ?? $this->createStub(NamespaceLookupFileGenerator::class),
            $environment,
        );
    }
}
