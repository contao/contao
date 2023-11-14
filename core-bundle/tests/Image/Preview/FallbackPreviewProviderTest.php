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

use Contao\CoreBundle\Image\Preview\FallbackPreviewProvider;
use Contao\CoreBundle\Image\Preview\UnableToGeneratePreviewException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\ImagineSvg\Imagine;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class FallbackPreviewProviderTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        (new Filesystem())->remove(Path::join($this->getTempDir(), 'assets/images/previews'));
    }

    public function testSupports(): void
    {
        $this->assertTrue((new FallbackPreviewProvider())->supports(Path::join($this->getTempDir(), 'foo/bar.something')));
    }

    public function testGetFileHeaderSize(): void
    {
        $this->assertSame(0, (new FallbackPreviewProvider())->getFileHeaderSize());
    }

    public function testGeneratePreviews(): void
    {
        $sourcePath = Path::join($this->getTempDir(), 'foo/bar.txt');
        $targetPath = Path::join($this->getTempDir(), 'assets/images/previews/bar');

        $targetPathCallback = function (int $page) use ($targetPath): string {
            $this->assertSame(1, $page);

            return $targetPath;
        };

        (new Filesystem())->mkdir(\dirname($targetPath));

        $this->assertSame(
            ["$targetPath.svg"],
            (new FallbackPreviewProvider())->generatePreviews($sourcePath, 1024, $targetPathCallback),
        );

        $size = (new Imagine())->open("$targetPath.svg")->getSize();

        $this->assertSame(1024, $size->getWidth());
        $this->assertSame(1024, $size->getHeight());
        $this->assertSame($size::TYPE_ABSOLUTE, $size->getType());

        $this->expectException(UnableToGeneratePreviewException::class);

        (new FallbackPreviewProvider())->generatePreviews($sourcePath, 1024, $targetPathCallback, 2, 2);
    }
}
