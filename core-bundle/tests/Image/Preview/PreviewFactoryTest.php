<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Image\Preview;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\Image\PictureFactoryInterface;
use Contao\CoreBundle\Image\Preview\MissingPreviewProviderException;
use Contao\CoreBundle\Image\Preview\PreviewFactory;
use Contao\CoreBundle\Image\Preview\PreviewProviderInterface;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image\Image;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class PreviewFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        (new Filesystem())->remove(Path::join($this->getTempDir(), 'assets/previews'));
        (new Filesystem())->remove(Path::join($this->getTempDir(), 'sources'));
    }

    public function testMissingPreviewProvider(): void
    {
        $sourcePath = Path::join($this->getTempDir(), 'sources/foo.pdf');

        $factory = new PreviewFactory(
            [],
            $this->createMock(ImageFactoryInterface::class),
            $this->createMock(PictureFactoryInterface::class),
            $this->createMock(Studio::class),
            $this->createMock(ContaoFramework::class),
            Path::join($this->getTempDir(), 'assets/previews'),
            ['png'],
            128,
            499,
        );

        (new Filesystem())->dumpFile($sourcePath, '%PDF-');

        $this->expectException(MissingPreviewProviderException::class);

        $factory->createPreview($sourcePath);
    }

    public function testCreatePreview(): void
    {
        $sourcePath = Path::join($this->getTempDir(), 'sources/foo.pdf');
        (new Filesystem())->dumpFile($sourcePath, '%PDF-');

        $factory = $this->createFactoryWithExampleProvider();

        $preview = $factory->createPreview($sourcePath);

        $this->assertFileExists($preview->getPath());
        $this->assertSame(128, $preview->getDimensions()->getSize()->getWidth());
        $this->assertSame(256, $preview->getDimensions()->getSize()->getHeight());
        $this->assertRegExp('(/[0-9a-z]/foo-[0-9a-z]{8}\.png$)', $preview->getPath());

        (new Filesystem())->dumpFile($sourcePath, 'not a PDF');
        $this->expectException(MissingPreviewProviderException::class);

        $factory->createPreview($sourcePath, 256);
    }

    public function testCreatePreviews(): void
    {
        $sourcePath = Path::join($this->getTempDir(), 'sources/foo.pdf');
        (new Filesystem())->dumpFile($sourcePath, '%PDF-');

        $factory = $this->createFactoryWithExampleProvider();

        $previews = $factory->createPreviews($sourcePath, 200, 3);

        $this->assertCount(3, $previews);

        foreach ($previews as $preview) {
            $this->assertFileExists($preview->getPath());
            $this->assertSame(256, $preview->getDimensions()->getSize()->getWidth());
            $this->assertSame(512, $preview->getDimensions()->getSize()->getHeight());
            $this->assertRegExp('(/[0-9a-z]/foo-[0-9a-z]{8}(-\d)?\.png$)', $preview->getPath());
        }

        $lastPagePath = substr($previews[0]->getPath(), 0, -4).'-last.png';

        $this->assertFileNotExists($lastPagePath);
        $previews = $factory->createPreviews($sourcePath, 200, 9999, 2);
        $this->assertCount(2, $previews);
        $this->assertFileExists($lastPagePath);

        $previews = $factory->createPreviews($sourcePath, 9999, 9999, 2);

        $this->assertCount(2, $previews);

        foreach ($previews as $preview) {
            $this->assertFileExists($preview->getPath());
            $this->assertSame(499, $preview->getDimensions()->getSize()->getWidth());
            $this->assertSame(998, $preview->getDimensions()->getSize()->getHeight());
            $this->assertRegExp('(/[0-9a-z]/foo-[0-9a-z]{8}-\d\.png$)', $preview->getPath());
        }

        $previews = $factory->createPreviews($sourcePath, 128, 9999, 4);

        $this->assertCount(0, $previews);

        (new Filesystem())->dumpFile($sourcePath, 'not a PDF');
        $this->expectException(MissingPreviewProviderException::class);

        $factory->createPreviews($sourcePath, 128);
    }

    private function createFactoryWithExampleProvider(): PreviewFactory
    {
        $pdfProvider = new class() implements PreviewProviderInterface {
            public function getFileHeaderSize(): int
            {
                return 5;
            }

            public function supports(string $path, string $fileHeader = '', array $options = []): bool
            {
                return '.pdf' === substr($path, -4) && 0 === strncmp($fileHeader, '%PDF-', 5);
            }

            public function generatePreviews(string $sourcePath, int $size, \Closure $targetPathCallback, int $lastPage = PHP_INT_MAX, int $firstPage = 1, array $options = []): \Generator
            {
                $lastPage = min(3, $lastPage);

                for ($page = $firstPage; $page <= $lastPage; ++$page) {
                    (new Imagine())
                        ->create(new Box($size, $size * 2))
                        ->save($targetPath = $targetPathCallback($page).'.png')
                    ;

                    yield $targetPath;
                }
            }
        };

        $imageFactory = $this->createMock(ImageFactoryInterface::class);
        $imageFactory
            ->method('create')
            ->willReturnCallback(
                function ($path) {
                    if ($path instanceof ImageInterface) {
                        return $path;
                    }

                    return new Image($path, $this->createMock(ImagineInterface::class));
                }
            )
        ;

        return new PreviewFactory(
            [$pdfProvider],
            $imageFactory,
            $this->createMock(PictureFactoryInterface::class),
            $this->createMock(Studio::class),
            $this->createMock(ContaoFramework::class),
            Path::join($this->getTempDir(), 'assets/previews'),
            ['png'],
            128,
            499,
        );
    }
}
