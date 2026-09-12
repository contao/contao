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
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

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

        $handler = new ResizeDeferredImageMessageHandler($imageFactory, $resizer, $this->createLockFactory());
        $handler($this->createMessage(ScopeAwareMessageInterface::SCOPE_CLI));
    }

    public function testDoesNotProcessImageInWebWorker(): void
    {
        $imageFactory = $this->createMock(ImageFactoryInterface::class);
        $imageFactory
            ->expects($this->never())
            ->method('create')
        ;

        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory
            ->expects($this->never())
            ->method('createLock')
        ;

        $handler = new ResizeDeferredImageMessageHandler(
            $imageFactory,
            $this->createStub(DeferredResizerInterface::class),
            $lockFactory,
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

        $handler = new ResizeDeferredImageMessageHandler($imageFactory, $resizer, $this->createLockFactory());
        $handler($this->createMessage(ScopeAwareMessageInterface::SCOPE_CLI));
    }

    private function createLockFactory(): LockFactory
    {
        $lock = $this->createMock(SharedLockInterface::class);
        $lock
            ->expects($this->once())
            ->method('acquire')
            ->with(true)
            ->willReturn(true)
        ;

        $lock
            ->expects($this->once())
            ->method('release')
        ;

        $lockFactory = $this->createStub(LockFactory::class);
        $lockFactory
            ->method('createLock')
            ->willReturn($lock)
        ;

        return $lockFactory;
    }

    private function createMessage(string $scope): ResizeDeferredImageMessage
    {
        return new ResizeDeferredImageMessage('image.jpg')->setScope($scope);
    }
}
