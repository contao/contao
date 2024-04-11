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

use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\Image\DeferredImageInterface;
use Contao\Image\DeferredResizerInterface;
use Contao\Image\Exception\FileNotExistsException;
use Contao\Image\ResizerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 */
class ImagesController
{
    public function __construct(
        private readonly ImageFactoryInterface $imageFactory,
        private readonly ResizerInterface $resizer,
        private readonly string $targetDir,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    /**
     * The route is registered dynamically in the
     * Contao\CoreBundle\Routing\ImagesLoader class.
     */
    public function __invoke(string $path): Response
    {
        try {
            try {
                $image = $this->imageFactory->create(Path::join($this->targetDir, $path));
            } catch (\InvalidArgumentException $exception) {
                throw new NotFoundHttpException($exception->getMessage(), $exception);
            }

            if ($image instanceof DeferredImageInterface && $this->resizer instanceof DeferredResizerInterface) {
                $this->resizer->resizeDeferredImage($image);
            } elseif (!$this->filesystem->exists($image->getPath())) {
                throw new NotFoundHttpException('Image does not exist');
            }
        } catch (FileNotExistsException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }

        return new BinaryFileResponse($image->getPath(), 200, ['Cache-Control' => 'private, max-age=31536000'], false);
    }
}
