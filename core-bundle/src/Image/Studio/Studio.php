<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Image\Studio;

use Contao\Image\ImageInterface;
use Contao\Image\PictureConfiguration;
use Contao\Image\ResizeOptions;
use Psr\Container\ContainerInterface;

class Studio
{
    /**
     * @param array<string> $validExtensions
     */
    public function __construct(
        private ContainerInterface $locator,
        private string $projectDir,
        private string $uploadPath,
        private string $webDir,
        private array $validExtensions,
    ) {
    }

    public function createFigureBuilder(): FigureBuilder
    {
        return new FigureBuilder($this->locator, $this->projectDir, $this->uploadPath, $this->webDir, $this->validExtensions);
    }

    public function createImage(ImageInterface|string $filePathOrImage, PictureConfiguration|array|int|string|null $sizeConfiguration, ResizeOptions $resizeOptions = null): ImageResult
    {
        return new ImageResult($this->locator, $this->projectDir, $filePathOrImage, $sizeConfiguration, $resizeOptions);
    }

    public function createLightboxImage(ImageInterface|string|null $filePathOrImage, string $url = null, PictureConfiguration|array|int|string|null $sizeConfiguration = null, string $groupIdentifier = null, ResizeOptions $resizeOptions = null): LightboxResult
    {
        return new LightboxResult($this->locator, $filePathOrImage, $url, $sizeConfiguration, $groupIdentifier, $resizeOptions);
    }
}
