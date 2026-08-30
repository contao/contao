<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Messenger\Message\ResizeDeferredImageMessage;
use Contao\CoreBundle\Messenger\WebWorker;
use Contao\Image\DeferredImageInterface;
use Contao\Image\DeferredResizerInterface;
use Contao\Image\ResizerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DeduplicateStamp;

/**
 * @internal
 */
class DeferredImageResponseFactory
{
    private readonly Filesystem $filesystem;

    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly ResizerInterface $resizer,
        private readonly WebWorker|null $webWorker = null,
    ) {
        $this->filesystem = new Filesystem();
    }

    public function create(DeferredImageInterface $image): Response|null
    {
        if ($this->filesystem->exists($image->getPath())) {
            return null;
        }

        if ($this->resize($image)) {
            return null;
        }

        if (!$this->webWorker || $this->webWorker->hasCliWorkersRunning()) {
            $this->dispatch($image->getPath());
        }

        $size = $image->getDimensions()->getSize();
        $content = \sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%2$d" viewBox="0 0 100 75" preserveAspectRatio="none"><path fill="#eee" d="M0 0h100v75H0z"/><path fill="#687787" d="M23.75 16.482a2.625 2.625 0 012.604-2.607h47.292a2.605 2.605 0 012.604 2.607v42.036a2.624 2.624 0 01-2.604 2.607H26.354a2.607 2.607 0 01-2.604-2.607zm47.251 2.643h-42v36.75l24.391-24.397a2.627 2.627 0 013.712 0l13.897 13.923zm-36.751 10.5a5.25 5.25 0 1010.501-.001 5.25 5.25 0 00-10.501.001"/></svg>',
            $size->getWidth(),
            $size->getHeight(),
        );

        return new Response(
            $content,
            Response::HTTP_ACCEPTED,
            [
                'Cache-Control' => 'private, no-store',
                'Content-Type' => 'image/svg+xml',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    private function resize(DeferredImageInterface $image): bool
    {
        if (!$this->resizer instanceof DeferredResizerInterface) {
            return false;
        }

        try {
            return $this->resizer->resizeDeferredImage($image, false)
                || $this->filesystem->exists($image->getPath());
        } catch (\Throwable) {
            return false;
        }
    }

    private function dispatch(string $path): void
    {
        try {
            $this->messageBus->dispatch(
                new ResizeDeferredImageMessage($path),
                [new DeduplicateStamp('contao-deferred-image-'.hash('sha256', $path))],
            );
        } catch (\Throwable) {
        }
    }
}
