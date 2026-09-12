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
use Contao\Image\Exception\FileNotExistsException;
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
    private readonly Filesystem $filesystem;

    public function __construct(
        private readonly ImageFactoryInterface $imageFactory,
        private readonly DeferredImageResponseFactory $deferredImageResponseFactory,
        private readonly string $targetDir,
    ) {
        $this->filesystem = new Filesystem();
    }

    /**
     * The route is registered dynamically in the
     * Contao\CoreBundle\Routing\ImagesLoader class.
     */
    public function __invoke(string $path): Response
    {
        $path = Path::join($this->targetDir, $path);

        if (!Path::isBasePath($this->targetDir, $path)) {
            throw new NotFoundHttpException('Image does not exist');
        }

        try {
            try {
                $image = $this->imageFactory->create($path);
            } catch (\InvalidArgumentException $exception) {
                throw new NotFoundHttpException($exception->getMessage(), $exception);
            }

            if ($image instanceof DeferredImageInterface) {
                if ($response = $this->deferredImageResponseFactory->create($image)) {
                    return $response;
                }
            } elseif (!$this->filesystem->exists($image->getPath())) {
                throw new NotFoundHttpException('Image does not exist');
            }
        } catch (FileNotExistsException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }

        return new BinaryFileResponse($image->getPath(), 200, ['Cache-Control' => 'private, max-age=31536000'], false);
    }
}
