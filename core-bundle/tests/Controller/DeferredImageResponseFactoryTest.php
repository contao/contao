<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller;

use Contao\CoreBundle\Controller\DeferredImageResponseFactory;
use Contao\CoreBundle\Cron\Cron;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image\DeferredImageInterface;
use Contao\Image\DeferredResizerInterface;
use Contao\Image\ImageDimensions;
use Contao\Image\ImageInterface;
use Imagine\Image\Box;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;

class DeferredImageResponseFactoryTest extends TestCase
{
    public function testReturnsPlaceholderIfCliCronIsActive(): void
    {
        $cron = $this->createStub(Cron::class);
        $cron
            ->method('hasMinutelyCliCron')
            ->willReturn(true)
        ;

        $factory = new DeferredImageResponseFactory($cron, $this->createStub(DeferredResizerInterface::class), new Filesystem());
        $response = $factory->create($this->createDeferredImage());

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
        $this->assertSame('image/svg+xml', $response->headers->get('Content-Type'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertTrue($response->headers->getCacheControlDirective('private'));
        $this->assertTrue($response->headers->getCacheControlDirective('no-store'));
        $this->assertStringStartsWith('<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180"', $response->getContent());
        $this->assertStringContainsString('<path fill="#687787"', $response->getContent());
    }

    public function testResizesSynchronouslyWithoutCliCron(): void
    {
        $cron = $this->createStub(Cron::class);
        $resizer = $this->createMock(DeferredResizerInterface::class);
        $image = $this->createDeferredImage();

        $resizer
            ->expects($this->once())
            ->method('resizeDeferredImage')
            ->with($image)
            ->willReturn($this->createStub(ImageInterface::class))
        ;

        $factory = new DeferredImageResponseFactory($cron, $resizer, new Filesystem());

        $this->assertNull($factory->create($image));
    }

    private function createDeferredImage(): DeferredImageInterface
    {
        $image = $this->createStub(DeferredImageInterface::class);
        $image
            ->method('getPath')
            ->willReturn($this->getTempDir().'/missing.jpg')
        ;

        $image
            ->method('getDimensions')
            ->willReturn(new ImageDimensions(new Box(320, 180)))
        ;

        return $image;
    }
}
