<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Messenger\MessageHandler;

use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\Messenger\Message\ResizeDeferredImageMessage;
use Contao\CoreBundle\Messenger\Message\ScopeAwareMessageInterface;
use Contao\Image\DeferredImageInterface;
use Contao\Image\DeferredResizerInterface;
use Contao\Image\Exception\FileNotExistsException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ResizeDeferredImageMessageHandler
{
    public function __construct(
        private readonly ImageFactoryInterface $imageFactory,
        private readonly DeferredResizerInterface $resizer,
        private readonly LockFactory $lockFactory,
    ) {
    }

    public function __invoke(ResizeDeferredImageMessage $message): void
    {
        if (ScopeAwareMessageInterface::SCOPE_CLI !== $message->getScope()) {
            return;
        }

        $lock = $this->lockFactory->createLock('contao-deferred-image-resize');

        if (!$lock->acquire(true)) {
            throw new \RuntimeException('Unable to acquire the deferred image resize lock.');
        }

        try {
            $image = $this->imageFactory->create($message->getPath());

            if ($image instanceof DeferredImageInterface) {
                $this->resizer->resizeDeferredImage($image, false);
            }
        } catch (FileNotExistsException) {
            // The recipe or source image may have been purged since dispatching the message.
        } finally {
            $lock->release();
        }
    }
}
