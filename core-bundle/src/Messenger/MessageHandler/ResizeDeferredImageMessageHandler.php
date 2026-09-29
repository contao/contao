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
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ResizeDeferredImageMessageHandler
{
    public function __construct(
        private readonly ImageFactoryInterface $imageFactory,
        private readonly DeferredResizerInterface $resizer,
    ) {
    }

    public function __invoke(ResizeDeferredImageMessage $message): void
    {
        if (ScopeAwareMessageInterface::SCOPE_CLI !== $message->getScope()) {
            return;
        }

        try {
            $image = $this->imageFactory->create($message->getPath());

            if ($image instanceof DeferredImageInterface) {
                $this->resizer->resizeDeferredImage($image, false);
            }
        } catch (FileNotExistsException) {
            // The recipe or source image may have been purged since dispatching the message.
        }
    }
}
