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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Image\ImageInterface;
use Contao\Image\PictureConfiguration;
use Contao\Image\ResizeOptions;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Psr\Container\ContainerInterface;

class LightboxResult
{
    private ContainerInterface $locator;
    private ?string $url;
    private ?string $groupIdentifier;
    private ?ImageResult $image = null;

    /**
     * @param string|ImageInterface|null                 $filePathOrImage
     * @param array|PictureConfiguration|int|string|null $sizeConfiguration
     *
     * @internal Use the Contao\CoreBundle\Image\Studio\Studio factory to get an instance of this class
     */
    public function __construct(ContainerInterface $locator, $filePathOrImage, ?string $url, $sizeConfiguration = null, string $groupIdentifier = null, ResizeOptions $resizeOptions = null)
    {
        if (1 !== \count(array_filter([$filePathOrImage, $url]))) {
            throw new \InvalidArgumentException('A lightbox must be either constructed with a resource or an URL.');
        }

        $this->locator = $locator;
        $this->url = $url;
        $this->groupIdentifier = $groupIdentifier;

        if (null !== $filePathOrImage) {
            $this->image = $locator
                ->get('contao.image.studio')
                ->createImage(
                    $filePathOrImage,
                    $sizeConfiguration ?? $this->getDefaultLightboxSizeConfiguration(),
                    $resizeOptions
                )
            ;
        }
    }

    /**
     * Returns true if this lightbox result contains an image.
     */
    public function hasImage(): bool
    {
        return null !== $this->image;
    }

    /**
     * Returns the image.
     */
    public function getImage(): ImageResult
    {
        if (!$this->hasImage()) {
            throw new \RuntimeException('This lightbox result does not contain an image.');
        }

        return $this->image;
    }

    /**
     * Returns the link URL pointing to the resource.
     */
    public function getLinkHref(): string
    {
        return $this->hasImage() ? $this->image->getImageSrc() : $this->url;
    }

    /**
     * Returns the lightbox group identifier.
     */
    public function getGroupIdentifier(): string
    {
        return $this->groupIdentifier ?? '';
    }

    /**
     * Returns the lightbox size configuration from the associated page layout.
     *
     * Will return null if there is no lightbox size configuration or if not
     * in a request context.
     */
    private function getDefaultLightboxSizeConfiguration(): ?array
    {
        $page = $GLOBALS['objPage'] ?? null;

        if (!$page instanceof PageModel || null === $page->layout) {
            return null;
        }

        /** @var ContaoFramework $framework */
        $framework = $this->locator->get('contao.framework');
        $layoutModel = $framework->getAdapter(LayoutModel::class)->findByPk($page->layout);

        if (null === $layoutModel || empty($layoutModel->lightboxSize)) {
            return null;
        }

        return StringUtil::deserialize($layoutModel->lightboxSize, true);
    }
}
