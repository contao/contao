<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\CoreBundle\Command\ResizeImagesCommand;
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image\DeferredImageInterface;
use Contao\Image\DeferredImageStorageInterface;
use Contao\Image\DeferredResizerInterface;
use Contao\Image\ImageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class ResizeImagesCommandTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $fs = new Filesystem();
        $fs->remove($this->getFixturesDir().'/assets/images');
    }

    public function testExecutesWithoutPendingImages(): void
    {
        $fs = new Filesystem();
        $fs->mkdir($this->getFixturesDir().'/assets/images');

        $storage = $this->createMock(DeferredImageStorageInterface::class);
        $storage
            ->method('listPaths')
            ->willReturn([])
        ;

        $command = $this->getCommand(null, null, $storage);
        $tester = new CommandTester($command);
        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);
        $this->assertRegExp('/All images resized/', $display);
    }

    public function testResizesImages(): void
    {
        $fs = new Filesystem();
        $fs->mkdir($this->getFixturesDir().'/assets/images');

        $factory = $this->createMock(ImageFactoryInterface::class);
        $factory
            ->method('create')
            ->willReturn($this->createMock(DeferredImageInterface::class))
        ;

        $resizer = $this->createMock(DeferredResizerInterface::class);
        $resizer
            ->method('resizeDeferredImage')
            ->willReturn($this->createMock(ImageInterface::class))
        ;

        $storage = $this->createMock(DeferredImageStorageInterface::class);
        $storage
            ->method('listPaths')
            ->willReturn(['image1.jpg', 'image2.jpg'])
        ;

        $command = $this->getCommand($factory, $resizer, $storage);
        $tester = new CommandTester($command);
        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);
        $this->assertRegExp('/image1.jpg.+done/', $display);
        $this->assertRegExp('/image2.jpg.+done/', $display);
        $this->assertRegExp('/All images resized/', $display);
    }

    public function testTimeLimit(): void
    {
        $fs = new Filesystem();
        $fs->mkdir($this->getFixturesDir().'/assets/images');

        $factory = $this->createMock(ImageFactoryInterface::class);
        $factory
            ->method('create')
            ->willReturn($this->createMock(DeferredImageInterface::class))
        ;

        $resizer = $this->createMock(DeferredResizerInterface::class);
        $resizer
            ->method('resizeDeferredImage')
            ->willReturnCallback(function () {
                usleep(1000);

                return $this->createMock(ImageInterface::class);
            })
        ;

        $storage = $this->createMock(DeferredImageStorageInterface::class);
        $storage
            ->method('listPaths')
            ->willReturn(['image1.jpg', 'image2.jpg'])
        ;

        $command = $this->getCommand($factory, $resizer, $storage);
        $tester = new CommandTester($command);
        $code = $tester->execute(['--time-limit' => 0.0001]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);
        $this->assertRegExp('/image1.jpg.+done/', $display);
        $this->assertRegExp('/Time limit of 0.0001 seconds reached/', $display);
        $this->assertNotRegExp('/image2.jpg.+done/', $display);
        $this->assertNotRegExp('/All images resized/', $display);
    }

    /**
     * @param ImageFactoryInterface&MockObject         $factory
     * @param DeferredResizerInterface&MockObject      $resizer
     * @param DeferredImageStorageInterface&MockObject $storage
     */
    private function getCommand(ImageFactoryInterface $factory = null, DeferredResizerInterface $resizer = null, DeferredImageStorageInterface $storage = null): ResizeImagesCommand
    {
        return new ResizeImagesCommand(
            $factory ?? $this->createMock(ImageFactoryInterface::class),
            $resizer ?? $this->createMock(DeferredResizerInterface::class),
            $this->getFixturesDir().'/assets/images',
            $storage ?? $this->createMock(DeferredImageStorageInterface::class)
        );
    }
}
