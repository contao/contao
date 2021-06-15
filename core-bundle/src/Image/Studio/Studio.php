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

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\Image\PictureFactoryInterface;
use Contao\Image\ImageInterface;
use Contao\Image\PictureConfiguration;
use Contao\Image\ResizeOptions;
use Contao\Image\ResizerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class Studio implements ServiceSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $locator;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var string
     */
    private $uploadPath;

    /**
     * @var array<string>
     */
    private $validExtensions;

    public function __construct(ContainerInterface $locator, string $projectDir, string $uploadPath, array $validExtensions)
    {
        $this->locator = $locator;
        $this->projectDir = $projectDir;
        $this->uploadPath = $uploadPath;
        $this->validExtensions = $validExtensions;
    }

    public function createFigureBuilder(): FigureBuilder
    {
        return new FigureBuilder($this->locator, $this->projectDir, $this->uploadPath, $this->validExtensions);
    }

    /**
     * @param string|ImageInterface $filePathOrImage
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

    public static function getSubscribedServices(): array
    {
        return [
            self::class,
            'contao.image.picture_factory' => PictureFactoryInterface::class,
            'contao.image.image_factory' => ImageFactoryInterface::class,
            'contao.image.resizer' => ResizerInterface::class,
            'contao.assets.files_context' => ContaoContext::class,
            'contao.framework' => ContaoFramework::class,
            'event_dispatcher' => 'event_dispatcher',
        ];
    }
}
