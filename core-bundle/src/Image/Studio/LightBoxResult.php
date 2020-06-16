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
    private $uri;

    /**
     * @readonly
     *
     * @var string|null
     */
    private $groupIdentifier;

    /**
     * @param string|ImageInterface|null                 $filePathOrImageInterface
     * @param array|PictureConfiguration|int|string|null $sizeConfiguration
     *
     * @internal Use the `contao.image.studio` factory to get an instance of this class.
     */
    public function __construct(ContainerInterface $locator, $filePathOrImageInterface, ?string $uri, $sizeConfiguration = null, string $groupIdentifier = null)
    {
        if (1 !== \count(array_filter([$filePathOrImageInterface, $uri]))) {
            throw new \InvalidArgumentException('A lightbox must be either constructed with a resource or an uri.');
        }

        $this->locator = $locator;
        $this->uri = $uri;
        $this->groupIdentifier = $groupIdentifier;

        if (null !== $filePathOrImageInterface) {
            $this->image = $locator
                ->get('contao.image.studio')
                ->createImage($filePathOrImageInterface, $sizeConfiguration ?? $this->getDefaultLightBoxSizeConfiguration())
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
        return $this->hasImage() ? $this->image->getImageSrc() : $this->uri;
    }

    /**
     * Return the light box group identifier.
     */
    public function getGroupId(): string
    {
        return $this->groupIdentifier ?? $this->createFallbackGroupIdentifier();
    }

    /**
     * Try to get a light box size configuration from the current page's
     * associated layout. Will return null if not defined or not in a request
     * context.
     */
    private function getDefaultLightBoxSizeConfiguration(): ?array
    {
        $request = $this->locator
            ->get('request_stack')
            ->getCurrentRequest()
        ;

        if (null === $request || !$request->attributes->has('pageModel')) {
            return null;
        }

        $framework = $this->locator->get('contao.framework');
        $framework->initialize();

        // Try to get page model // todo: what's the right way to do this?
        $page = $request->attributes->get('pageModel');

        if (!$page instanceof PageModel) {
            /** @var PageModel $pageAdapter */
            $pageAdapter = $framework->getAdapter(PageModel::class);

            /** @var PageModel|null $page */
            $page = $pageAdapter->findByPk($request->attributes->get('pageModel'));
        }

        if (null === $page || null === $page->layout) {
            return null;
        }

        $page->loadDetails();

        // Try to get layout
        /** @var LayoutModel $layoutModelAdapter */
        $layoutModelAdapter = $framework->getAdapter(LayoutModel::class);

        /** @var LayoutModel|null $layoutModel */
        $layoutModel = $layoutModelAdapter->findByPk($page->layout);

        if (null === $layoutModel || empty($layoutModel->lightboxSize)) {
            return null;
        }

        return StringUtil::deserialize($layoutModel->lightboxSize, true);
    }

    /**
     * Create a pseudo-unique identifier. This prevents independent images
     * being grouped under the same (empty) identifier.
     */
    private function createFallbackGroupIdentifier(): string
    {
        return substr(md5($this->getLinkHref()), 0, 6);
    }
}
