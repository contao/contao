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
use Contao\Image\ResizerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 */
class ImagesController
{
    /**
     * @var ImageFactoryInterface
     */
    private $imageFactory;

    /**
     * @var ResizerInterface
     */
    private $resizer;

    /**
     * @var string
     */
    private $targetDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(ImageFactoryInterface $imageFactory, ResizerInterface $resizer, string $targetDir, Filesystem $filesystem = null)
    {
        $this->imageFactory = $imageFactory;
        $this->resizer = $resizer;
        $this->targetDir = $targetDir;
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    /**
     * The route is registered dynamically in the Contao\CoreBundle\Routing\ImagesLoader class.
     */
    public function __invoke(string $path): Response
    {
        try {
            $image = $this->imageFactory->create($this->targetDir.'/'.$path);
        } catch (\Exception $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }

        $resizer = $this->resizer;

        if ($image instanceof DeferredImageInterface && $resizer instanceof DeferredResizerInterface) {
            $resizer->resizeDeferredImage($image);
        } elseif (!$this->filesystem->exists($image->getPath())) {
            throw new NotFoundHttpException('Image does not exist');
        }

        return new BinaryFileResponse($image->getPath(), 200, ['Cache-Control' => 'private, max-age=31536000'], false);
    }
}
