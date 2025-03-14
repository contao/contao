<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\Twig\IDE;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\IDE\NamespaceLookupFileGenerator;
use Contao\CoreBundle\Twig\IDE\NamespaceLookupFileWarmer;
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
            ->with('/project/var/contao-twig')
        ;

        $this->getNamespaceLookupFileWarmer($namespaceLookupFileGenerator)->warmUp('');
    }

    public function testDoesNotWriteFileInProd(): void
    {
        $namespaceLookupFileGenerator = $this->createMock(NamespaceLookupFileGenerator::class);
        $namespaceLookupFileGenerator
            ->expects($this->never())
            ->method('write')
        ;

        $this->getNamespaceLookupFileWarmer($namespaceLookupFileGenerator, 'prod')->warmUp('');
    }

    public function testToleratesFailingWrites(): void
    {
        $namespaceLookupFileGenerator = $this->createMock(NamespaceLookupFileGenerator::class);
        $namespaceLookupFileGenerator
            ->expects($this->once())
            ->method('write')
            ->willThrowException(new IOException('Unable to write'))
        ;

        $this->getNamespaceLookupFileWarmer($namespaceLookupFileGenerator)->warmUp('');
    }

    private function getNamespaceLookupFileWarmer(NamespaceLookupFileGenerator|null $namespaceLookupFileGenerator = null, string $environment = 'dev'): NamespaceLookupFileWarmer
    {
        return new NamespaceLookupFileWarmer(
            $namespaceLookupFileGenerator ?? $this->createMock(NamespaceLookupFileGenerator::class),
            $environment,
            '/project',
        );
    }
}
