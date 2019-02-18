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

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\LogoutException;
use Contao\Image\DeferredResizerInterface;
use Imagine\Image\ImagineInterface;

class ImagesController
{
    /**
     * @var DeferredResizerInterface
     */
    private $resizer;

    /**
     * @var ImagineInterface
     */
    private $imagine;

    /**
     * @var ImagineInterface
     */
    private $imagineSvg;

    public function __construct(DeferredResizerInterface $resizer, ImagineInterface $imagine, ImagineInterface $imagineSvg)
    {
        $this->resizer = $resizer;
        $this->imagine = $imagine;
        $this->imagineSvg = $imagineSvg;
    }

    /**
     * @Route("/assets/images/{path<.+>}", name="contao_images")
     */
    public function index(string $path): Response
    {
        if (\in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['svg', 'svgz'], true)) {
            $imagine = $this->imagineSvg;
        } else {
            $imagine = $this->imagine;
        }

        try {
            $image = $this->resizer->resizeDeferredImage($path, $imagine);
        } catch (\Throwable $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }

        return new BinaryFileResponse($image->getPath());
    }
}
