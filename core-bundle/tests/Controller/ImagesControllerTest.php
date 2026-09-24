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
use Contao\CoreBundle\Controller\ImagesController;
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image\DeferredImageInterface;
use Contao\Image\Exception\FileNotExistsException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ImagesControllerTest extends TestCase
{
    public function testReturnsResizedImage(): void
    {
        $image = $this->createStub(DeferredImageInterface::class);
        $image
            ->method('getPath')
            ->willReturn($this->getFixturesDir().'/images/dummy.jpg')
        ;

        $factory = $this->createStub(ImageFactoryInterface::class);
        $factory
            ->method('create')
            ->willReturn($image)
        ;

        $responseFactory = $this->createStub(DeferredImageResponseFactory::class);
        $controller = new ImagesController($factory, $responseFactory, $this->getFixturesDir().'/images');

        $response = $controller('image.jpg');

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame($this->getFixturesDir().'/images/dummy.jpg', $response->getFile()->getPathname());
        $this->assertSame('31536000', $response->headers->getCacheControlDirective('max-age'));
        $this->assertTrue($response->headers->getCacheControlDirective('private'));
    }

    public function testReturns404IfImageDoesNotExist(): void
    {
        if (class_exists(FileNotExistsException::class)) {
            $exception = new FileNotExistsException('Image does not exist');
        } else {
            $exception = new \InvalidArgumentException('Image does not exist');
        }

        $factory = $this->createStub(ImageFactoryInterface::class);
        $factory
            ->method('create')
            ->willThrowException($exception)
        ;

        $controller = new ImagesController($factory, $this->createStub(DeferredImageResponseFactory::class), $this->getFixturesDir().'/images');

        $this->expectException(NotFoundHttpException::class);

        $controller('image.jpg');
    }

    public function testReturnsUncachedPlaceholderForDeferredImage(): void
    {
        $image = $this->createStub(DeferredImageInterface::class);
        $image
            ->method('getPath')
            ->willReturn($this->getTempDir().'/missing.jpg')
        ;

        $factory = $this->createStub(ImageFactoryInterface::class);
        $factory
            ->method('create')
            ->willReturn($image)
        ;

        $response = new Response('', Response::HTTP_ACCEPTED);
        $responseFactory = $this->createMock(DeferredImageResponseFactory::class);
        $responseFactory
            ->method('create')
            ->with($image)
            ->willReturn($response)
        ;

        $controller = new ImagesController($factory, $responseFactory, $this->getFixturesDir().'/images');
        $response = $controller('image.jpg');

        $this->assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
    }

    public function testReturns404IfImageIsOutsidePath(): void
    {
        $factory = $this->createMock(ImageFactoryInterface::class);
        $factory
            ->expects($this->never())
            ->method('create')
        ;

        $resizer = $this->createStub(ResizerInterface::class);
        $controller = new ImagesController($factory, $resizer, $this->getFixturesDir().'/images/sub-directory');

        $this->expectException(NotFoundHttpException::class);

        $controller('../dummy.jpg');
    }
}
