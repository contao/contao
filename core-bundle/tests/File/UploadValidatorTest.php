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

use Contao\Config;
use Contao\CoreBundle\File\ImageTooLargeException;
use Contao\CoreBundle\File\InvalidImageException;
use Contao\CoreBundle\File\UploadValidator;
use Contao\FileUpload;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Metadata\MetadataBag;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class UploadValidatorTest extends ContaoTestCase
{
    #[DataProvider('filenames')]
    public function testValidatesFilenames(string $name, bool $valid): void
    {
        $this->assertSame($valid, $this->validator()->isValidFilename($name));
    }

    public static function filenames(): iterable
    {
        yield ['example.txt', true];
        yield ['über uns.PNG', true];
        yield ['.public', false];
        yield ['.hidden.txt', false];
        yield ['', false];
        yield ['../example.txt', false];
        yield ['folder\\example.txt', false];
        yield ['file:stream.txt', false];
        yield ["file\0.txt", false];
        yield ["\xff.txt", false];
        yield [str_repeat('a', 256), false];
    }

    public function testValidatesConfiguredExtensions(): void
    {
        $validator = $this->validator();
        $this->assertTrue($validator->isAllowedFilename('image.SVG'));
        $this->assertFalse($validator->isAllowedFilename('image.svg.php'));
        $this->assertFalse($validator->isAllowedFilename('file'));
    }

    public function testSanitizesPlainAndCompressedSvg(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="1" height="1"/></svg>';
        $validator = $this->validator();
        $sanitized = $validator->sanitizeSvg($svg);
        $this->assertNotNull($sanitized);
        $this->assertStringNotContainsString('<script', $sanitized);
        $this->assertStringContainsString('<rect', $sanitized);
        $this->assertSame($sanitized, gzdecode($validator->sanitizeSvg(gzencode($svg))));
        $this->assertNull($validator->sanitizeSvg('not XML'));
        $this->assertNull($validator->sanitizeSvg(''));
    }

    public function testImageValidationIsConditional(): void
    {
        $this->assertTrue($this->validator()->requiresImageValidation('image.PNG'));
        $this->assertFalse($this->validator()->requiresImageValidation('image.svg'));
        $this->assertFalse($this->validator(false)->requiresImageValidation('image.png'));
        $this->assertFalse($this->validator(width: 0)->requiresImageValidation('image.png'));
        $this->assertFalse($this->validator(height: 0)->requiresImageValidation('image.png'));
    }

    public function testAcceptsImageAtDimensionLimit(): void
    {
        $path = __DIR__.'/../Fixtures/images/favicon.png';
        [$width, $height] = getimagesize($path);
        $this->validator(width: $width, height: $height)->validateImage($path);
        $this->addToAssertionCount(1);
    }

    public function testDistinguishesLargeAndUnreadableImages(): void
    {
        foreach ([['not an image', InvalidImageException::class], [file_get_contents(__DIR__.'/../Fixtures/images/favicon.png'), ImageTooLargeException::class]] as [$content, $reason]) {
            $path = self::getTempDir().'/image.png';
            file_put_contents($path, $content);

            try {
                $this->validator()->validateImage($path);
                $this->fail('Accepted invalid image.');
            } catch (ImageTooLargeException|InvalidImageException $exception) {
                $this->assertSame($reason, $exception::class);
            }
        }
    }

    public function testLegacySvgWrapperPreservesItsFileAndBooleanContract(): void
    {
        $previousContainer = System::getContainer();
        $container = new ContainerBuilder();
        $container->set('contao.file.upload_validator', $this->validator());
        System::setContainer($container);

        try {
            $path = self::getTempDir().'/image.svgz';
            file_put_contents($path, gzencode('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="1" height="1"/></svg>'));
            $this->assertTrue(FileUpload::sanitizeSvg($path));
            $this->assertStringNotContainsString('<script', gzdecode(file_get_contents($path)));
            $this->assertStringContainsString('<rect', gzdecode(file_get_contents($path)));
            file_put_contents($path, 'invalid');
            $this->assertFalse(FileUpload::sanitizeSvg($path));
            $this->assertSame('invalid', file_get_contents($path));
        } finally {
            new \ReflectionProperty(System::class, 'objContainer')->setValue(null, $previousContainer);
        }
    }

    public function testUsesImagineForFormatsNotRecognizedByPhp(): void
    {
        $path = self::getTempDir().'/image';
        file_put_contents($path, 'format handled by the configured Imagine backend');
        $image = $this->createStub(ImageInterface::class);
        $image
            ->method('metadata')
            ->willReturn(new MetadataBag())
        ;

        $image
            ->method('getSize')
            ->willReturn(new Box(2, 1))
        ;
        $imagine = $this->createMock(ImagineInterface::class);
        $imagine
            ->expects($this->once())
            ->method('open')
            ->with($path)
            ->willReturn($image)
        ;

        $this->expectException(ImageTooLargeException::class);
        $this->validator(imagine: $imagine)->validateImage($path);
    }

    #[DataProvider('configurationOperations')]
    public function testInitializesFrameworkBeforeReadingConfiguration(string $operation): void
    {
        $calls = [];
        $config = $this->createAdapterStub(['get']);
        $config
            ->method('get')
            ->willReturnCallback(
                static function (string $key) use (&$calls) {
                    $calls[] = 'config';

                    return match ($key) {
                        'uploadTypes' => 'png',
                        'imageWidth', 'imageHeight' => 1000,
                        default => null,
                    };
                },
            )
        ;
        $framework = $this->createContaoFrameworkMock([Config::class => $config]);
        $framework
            ->expects($this->once())
            ->method('initialize')
            ->willReturnCallback(
                static function () use (&$calls): void {
                    $calls[] = 'initialize';
                },
            )
        ;
        $validator = new UploadValidator($framework, true, new Imagine());
        $validator->$operation(__DIR__.'/../Fixtures/images/favicon.png');
        $this->assertSame('initialize', $calls[0]);
    }

    public static function configurationOperations(): iterable
    {
        yield ['isAllowedFilename'];
        yield ['requiresImageValidation'];
        yield ['validateImage'];
    }

    private function validator(bool $rejectLargeUploads = true, int $width = 1, int $height = 1, ImagineInterface|null $imagine = null): UploadValidator
    {
        $config = $this->createAdapterStub(['get']);
        $config
            ->method('get')
            ->willReturnMap([
                ['uploadTypes', 'txt,svg,svgz,png'],
                ['imageWidth', $width],
                ['imageHeight', $height],
            ])
        ;

        return new UploadValidator($this->createContaoFrameworkStub([Config::class => $config]), $rejectLargeUploads, $imagine ?? new Imagine());
    }
}
