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
    private ImageResult|null $image = null;

    /**
     * @internal Use the Contao\CoreBundle\Image\Studio\Studio factory to get an instance of this class
     */
    public function __construct(
        private readonly ContainerInterface $locator,
        ImageInterface|string|null $filePathOrImage,
        private readonly string|null $url,
        PictureConfiguration|array|int|string|null $sizeConfiguration = null,
        private readonly string|null $groupIdentifier = null,
        ResizeOptions|null $resizeOptions = null,
    ) {
        if (1 !== \count(array_filter([$filePathOrImage, $url]))) {
            throw new \InvalidArgumentException('A lightbox must be either constructed with a resource or an URL.');
        }

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
        return $this->image instanceof ImageResult;
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
    private function getDefaultLightboxSizeConfiguration(): array|null
    {
        $page = $GLOBALS['objPage'] ?? null;

        if (!$page instanceof PageModel || null === $page->layout) {
            return null;
        }

        /** @var ContaoFramework $framework */
        $framework = $this->locator->get('contao.framework');
        $layoutModel = $framework->getAdapter(LayoutModel::class)->findByPk($page->layout);

        if (!$layoutModel instanceof LayoutModel || empty($layoutModel->lightboxSize)) {
            return null;
        }

        return StringUtil::deserialize($layoutModel->lightboxSize, true);
    }
}
