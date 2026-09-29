<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Messenger\MessageHandler;

use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\Messenger\Message\ResizeDeferredImageMessage;
use Contao\CoreBundle\Messenger\Message\ScopeAwareMessageInterface;
use Contao\CoreBundle\Messenger\MessageHandler\ResizeDeferredImageMessageHandler;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image\DeferredImageInterface;
use Contao\Image\DeferredResizerInterface;
use Contao\Image\Exception\FileNotExistsException;

class ResizeDeferredImageMessageHandlerTest extends TestCase
{
    public function testProcessesImageOnCli(): void
    {
        $image = $this->createStub(DeferredImageInterface::class);

        $imageFactory = $this->createStub(ImageFactoryInterface::class);
        $imageFactory
            ->method('create')
            ->willReturn($image)
        ;

        $resizer = $this->createMock(DeferredResizerInterface::class);
        $resizer
            ->expects($this->once())
            ->method('resizeDeferredImage')
            ->with($image, false)
        ;

        $handler = new ResizeDeferredImageMessageHandler($imageFactory, $resizer);
        $handler($this->createMessage(ScopeAwareMessageInterface::SCOPE_CLI));
    }

    public function testDoesNotProcessImageInWebWorker(): void
    {
        $imageFactory = $this->createMock(ImageFactoryInterface::class);
        $imageFactory
            ->expects($this->never())
            ->method('create')
        ;

        $handler = new ResizeDeferredImageMessageHandler(
            $imageFactory,
            $this->createStub(DeferredResizerInterface::class),
        );

        $handler($this->createMessage(ScopeAwareMessageInterface::SCOPE_WEB));
    }

    public function testTreatsPurgedRecipeAsSuccessful(): void
    {
        $imageFactory = $this->createStub(ImageFactoryInterface::class);
        $imageFactory
            ->method('create')
            ->willThrowException(new FileNotExistsException('Recipe was purged.'))
        ;

        $resizer = $this->createMock(DeferredResizerInterface::class);
        $resizer
            ->expects($this->never())
            ->method('resizeDeferredImage')
        ;

        $handler = new ResizeDeferredImageMessageHandler($imageFactory, $resizer);
        $handler($this->createMessage(ScopeAwareMessageInterface::SCOPE_CLI));
    }

    private function createMessage(string $scope): ResizeDeferredImageMessage
    {
        return new ResizeDeferredImageMessage('image.jpg')->setScope($scope);
    }
}
