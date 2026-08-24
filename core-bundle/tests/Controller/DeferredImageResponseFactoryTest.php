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
use Contao\CoreBundle\Messenger\Message\ResizeDeferredImageMessage;
use Contao\CoreBundle\Messenger\WebWorker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image\DeferredImageInterface;
use Contao\Image\DeferredResizerInterface;
use Contao\Image\ImageDimensions;
use Contao\Image\ImageInterface;
use Imagine\Image\Box;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DeduplicateStamp;

class DeferredImageResponseFactoryTest extends TestCase
{
    public function testReturnsPlaceholderWithoutWebWorker(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(static fn (object $message): Envelope => new Envelope($message))
        ;

        $factory = new DeferredImageResponseFactory($messageBus, $this->createStub(DeferredResizerInterface::class));
        $response = $factory->create($this->createDeferredImage());

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
    }

    public function testReturnsPlaceholderIfAsyncProcessingIsAvailable(): void
    {
        $webWorker = $this->createStub(WebWorker::class);
        $webWorker
            ->method('hasCliWorkersRunning')
            ->willReturn(true)
        ;

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(fn (ResizeDeferredImageMessage $message): bool => $this->getTempDir().'/missing.jpg' === $message->getPath()),
                $this->callback(static fn (array $stamps): bool => $stamps[0] instanceof DeduplicateStamp),
            )
            ->willReturnCallback(static fn (object $message): Envelope => new Envelope($message))
        ;

        $factory = new DeferredImageResponseFactory($messageBus, $this->createStub(DeferredResizerInterface::class), $webWorker);
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

    public function testResizesSynchronouslyWithoutAsyncProcessing(): void
    {
        $webWorker = $this->createStub(WebWorker::class);
        $webWorker
            ->method('hasCliWorkersRunning')
            ->willReturn(false)
        ;

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->expects($this->never())
            ->method('dispatch')
        ;

        $resizer = $this->createMock(DeferredResizerInterface::class);
        $image = $this->createDeferredImage();

        $resizer
            ->expects($this->once())
            ->method('resizeDeferredImage')
            ->with($image)
            ->willReturn($this->createStub(ImageInterface::class))
        ;

        $factory = new DeferredImageResponseFactory($messageBus, $resizer, $webWorker);

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
