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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    public function __construct(ImageFactoryInterface $imageFactory, ResizerInterface $resizer, string $targetDir)
    {
        $this->imageFactory = $imageFactory;
        $this->resizer = $resizer;
        $this->targetDir = $targetDir;
    }

    /**
     * The route is registered dynamically in the Contao\CoreBundle\Routing\ImagesLoader class.
     */
    public function __invoke(string $path): Response
    {
        try {
            $image = $this->imageFactory->create($this->targetDir.'/'.$path);
            $resizer = $this->resizer;

            if ($image instanceof DeferredImageInterface && $resizer instanceof DeferredResizerInterface) {
                $resizer->resizeDeferredImage($image);
            }
        } catch (\Exception $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }

        return new BinaryFileResponse($image->getPath());
    }
}
