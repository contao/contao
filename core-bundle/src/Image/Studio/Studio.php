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
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class Studio implements ServiceSubscriberInterface
{
    /**
     * @readonly
     *
     * @var ContainerInterface
     */
    private $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public function createFigureBuilder(): FigureBuilder
    {
        return new FigureBuilder($this->locator);
    }

    /**
     * @param string|ImageInterface                      $filePathOrImage
     * @param array|PictureConfiguration|int|string|null $sizeConfiguration
     */
    public function createImage($filePathOrImage, $sizeConfiguration): ImageResult
    {
        return new ImageResult($this->locator, $filePathOrImage, $sizeConfiguration);
    }

    /**
     * @param string|ImageInterface|null                 $filePathOrImage
     * @param array|PictureConfiguration|int|string|null $sizeConfiguration
     */
    public function createLightBoxImage($filePathOrImage, string $url = null, $sizeConfiguration = null, string $groupIdentifier = null): LightBoxResult
    {
        return new LightBoxResult($this->locator, $filePathOrImage, $url, $sizeConfiguration, $groupIdentifier);
    }

    public static function getSubscribedServices(): array
    {
        return [
            'contao.image.studio' => self::class,
            'contao.image.picture_factory' => PictureFactoryInterface::class,
            'contao.image.image_factory' => ImageFactoryInterface::class,
            'request_stack' => RequestStack::class,
            'parameter_bag' => ParameterBagInterface::class,
            'contao.assets.files_context' => ContaoContext::class,
            'contao.framework' => ContaoFramework::class,
        ];
    }
}
