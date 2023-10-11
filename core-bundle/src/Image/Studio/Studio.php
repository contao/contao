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
    private ContainerInterface $locator;
    private string $projectDir;
    private string $uploadPath;
    private string $webDir;

    /**
     * @var array<string>
     */
    private array $validExtensions;

    public function __construct(ContainerInterface $locator, string $projectDir, string $uploadPath, string $webDir, array $validExtensions)
    {
        $this->locator = $locator;
        $this->projectDir = $projectDir;
        $this->uploadPath = $uploadPath;
        $this->webDir = $webDir;
        $this->validExtensions = $validExtensions;
    }

    public function createFigureBuilder(): FigureBuilder
    {
        return new FigureBuilder($this->locator, $this->projectDir, $this->uploadPath, $this->webDir, $this->validExtensions);
    }

    /**
     * @param string|ImageInterface                      $filePathOrImage
     * @param array|PictureConfiguration|int|string|null $sizeConfiguration
     */
    public function createImage($filePathOrImage, $sizeConfiguration, ResizeOptions $resizeOptions = null): ImageResult
    {
        return new ImageResult($this->locator, $this->projectDir, $filePathOrImage, $sizeConfiguration, $resizeOptions);
    }

    /**
     * @param string|ImageInterface|null                 $filePathOrImage
     * @param array|PictureConfiguration|int|string|null $sizeConfiguration
     */
    public function createLightboxImage($filePathOrImage, string $url = null, $sizeConfiguration = null, string $groupIdentifier = null, ResizeOptions $resizeOptions = null): LightboxResult
    {
        return new LightboxResult($this->locator, $filePathOrImage, $url, $sizeConfiguration, $groupIdentifier, $resizeOptions);
    }
}
