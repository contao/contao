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
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Psr\Container\ContainerInterface;

class LightBoxResult
{
    /**
     * @readonly
     *
     * @var ContainerInterface
     */
    protected $locator;

    /**
     * @readonly
     *
     * @var ImageResult|null
     */
    private $image;

    /**
     * @readonly
     *
     * @var string|null
     */
    private $url;

    /**
     * @readonly
     *
     * @var string|null
     */
    private $groupIdentifier;

    /**
     * @param string|ImageInterface|null                 $filePathOrImage
     * @param array|PictureConfiguration|int|string|null $sizeConfiguration
     *
     * @internal use the Contao\Image\Studio\Studio factory to get an instance of this class
     */
    public function __construct(ContainerInterface $locator, $filePathOrImage, ?string $url, $sizeConfiguration = null, string $groupIdentifier = null)
    {
        if (1 !== \count(array_filter([$filePathOrImage, $url]))) {
            throw new \InvalidArgumentException('A lightbox must be either constructed with a resource or an URL.');
        }

        $this->locator = $locator;
        $this->url = $url;
        $this->groupIdentifier = $groupIdentifier;

        if (null !== $filePathOrImage) {
            $this->image = $locator
                ->get(Studio::class)
                ->createImage($filePathOrImage, $sizeConfiguration ?? $this->getDefaultLightBoxSizeConfiguration())
            ;
        }
    }

    /**
     * Return if this light box result contains a (resized) image.
     */
    public function hasImage(): bool
    {
        return null !== $this->image;
    }

    /**
     * Return the underlying (resized) image.
     */
    public function getImage(): ImageResult
    {
        if (!$this->hasImage()) {
            throw new \RuntimeException('This light box result does not contain a (resized) image.');
        }

        return $this->image;
    }

    /**
     * Return the link url pointing to the resource.
     */
    public function getLinkHref(): string
    {
        return $this->hasImage() ? $this->image->getImageSrc() : $this->url;
    }

    /**
     * Return the light box group identifier.
     */
    public function getGroupIdentifier(): string
    {
        return $this->groupIdentifier ?? '';
    }

    /**
     * Try to get a light box size configuration from the current page's
     * associated layout. Will return null if not defined or not in a request
     * context.
     */
    private function getDefaultLightBoxSizeConfiguration(): ?array
    {
        $framework = $this->locator->get('contao.framework');
        $page = $GLOBALS['objPage'] ?? null;

        if (!$page instanceof PageModel || null === $page->layout) {
            return null;
        }

        /** @var LayoutModel $layoutModelAdapter */
        $layoutModelAdapter = $framework->getAdapter(LayoutModel::class);

        /** @var LayoutModel|null $layoutModel */
        $layoutModel = $layoutModelAdapter->findByPk($page->layout);

        if (null === $layoutModel || empty($layoutModel->lightboxSize)) {
            return null;
        }

        return StringUtil::deserialize($layoutModel->lightboxSize, true);
    }
}
