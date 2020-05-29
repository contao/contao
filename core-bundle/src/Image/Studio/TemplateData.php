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

use Contao\Image\ImageDimensions;
use Contao\Template;

/**
 * This class is a proxy for template data created by the image studio. It
 * provides a convenient way to access data in a structured form for custom
 * business logic or the use inside Twig (e.g. get HTML attributes as key
 * value pairs) as well as in a preprocessed form ready to use in Contao
 * templates (e.g. get HTML attributes as a rendered string).
 */
final class TemplateData
{
    public const LINK_NONE = 0;
    public const LINK_LIGHTBOX = 1;
    public const LINK_NEW_WINDOW = 2;

    /**
     * @var Studio
     */
    private $studio;

    /**
     * @var Studio|null
     */
    private $secondaryStudio;

    /**
     * The mode on how to interpret url data (e.g. open in a new window vs. in a light box).
     *
     * @var int
     */
    private $linkMode;

    /**
     * User defined custom light box identifier (used in `data-lightbox` attribute).
     *
     * @var string|null
     */
    private $lightBoxId;

    /**
     * Legacy floating property.
     *
     * @var string|null
     */
    private $floatingProperty;

    /**
     * Legacy margin property.
     *
     * @var string|null
     */
    private $marginProperty;

    public function __construct(Studio $studio, int $linkMode = self::LINK_NONE, ?Studio $secondaryStudio = null)
    {
        $this->studio = $studio;
        $this->linkMode = $linkMode;
        $this->secondaryStudio = $secondaryStudio;
    }

    /**
     * Overwrite the default mode on how to interpret url data.
     */
    public function setLinkMode(int $linkMode): self
    {
        $this->linkMode = $linkMode;

        return $this;
    }

    /**
     * Define a custom light box id. By default a random id is generated.
     */
    public function setLightBoxId(?string $id): self
    {
        $this->lightBoxId = $id;

        return $this;
    }

    /**
     * Set the legacy floating property (used to set a `float_*` class and to
     * determine the `addBefore` property when exporting the data for Contao
     * templates).
     */
    public function setFloating(?string $direction): self
    {
        // todo: Should we trigger a deprecation warning here?

        $this->floatingProperty = $direction;

        return $this;
    }

    /**
     * Set the legacy margin property (transparently being output as the `margin`
     * when exporting the data for Contao templates).
     */
    public function setMargin(?string $cssValues): self
    {
        // todo: Should we trigger a deprecation warning here?
        //       Should we drop this option and only handle it in `Controller::addImageToTemplate`?

        $this->marginProperty = $cssValues;

        return $this;
    }

    public function getMetaData(): MetaData
    {
        return $this->studio->getMetaData();
    }

    public function getImg(): array
    {
        return $this->studio->getImg();
    }

    public function getSources(): array
    {
        return $this->studio->getSources();
    }

    public function getOriginalDimensions(): ImageDimensions
    {
        return $this->studio->getOriginalDimensions();
    }

    public function hasSecondaryImage(): bool
    {
        return null !== $this->secondaryStudio;
    }

    public function getSecondaryImg(): array
    {
        if (!$this->hasSecondaryImage()) {
            throw new \LogicException('There is no secondary image available.');
        }

        return $this->secondaryStudio->getImg();
    }

    public function getSecondarySources(): array
    {
        if (!$this->hasSecondaryImage()) {
            throw new \LogicException('There is no secondary image available.');
        }

        return $this->secondaryStudio->getSources();
    }

    public function getLinkMode(): int
    {
        return $this->linkMode;
    }

    /**
     * Get the link/light box attributes as key value pairs.
     */
    public function getAttributes(): array
    {
        if (self::LINK_NEW_WINDOW === $this->linkMode) {
            return ['target' => '_blank'];
        }

        if (self::LINK_LIGHTBOX === $this->linkMode) {
            return ['data-lightbox' => $this->lightBoxId ?? $this->getFallbackLightBoxId()];
        }

        return [];
    }

    /**
     * Get the link uri or null if none exists.
     */
    public function getHref(): ?string
    {
        if (self::LINK_NONE === $this->linkMode) {
            return null;
        }

        if (self::LINK_NEW_WINDOW === $this->linkMode) {
            return $this->studio->getMetaData()->getUrl();
        }

        if (null !== $this->secondaryStudio) {
            $img = $this->secondaryStudio->getImg();

            return $img['src'];
        }

        return null;
    }

    /**
     * Compile a data set ready to be applied to a Contao template.
     */
    public function getData(): array
    {
        $metaData = $this->getMetaData();
        $originalSize = $this->getOriginalDimensions()->getSize();

        // Primary image and meta data
        $templateData = array_merge(
            $metaData->getAll(),
            [
                'picture' => [
                    'img' => $this->getImg(),
                    'sources' => $this->getSources(),
                    'alt' => $metaData->getAlt(),
                ],
                'width' => $originalSize->getWidth(),
                'height' => $originalSize->getHeight(),
                'arrSize' => [$originalSize->getWidth(), $originalSize->getHeight()],
                'imgSize' => sprintf(' width="%d" height="%d"', $originalSize->getWidth(), $originalSize->getHeight()),
                'singleSRC' => $this->studio->getFilePath(),
                'fullsize' => \in_array($this->linkMode, [self::LINK_NEW_WINDOW, self::LINK_LIGHTBOX], true),
                'margin' => $this->marginProperty ?? '',
                'addBefore' => 'below' !== $this->floatingProperty,
                'addImage' => true,
            ]
        );

        // Context sensitive properties
        if (null !== ($title = $metaData->getTitle())) {
            $templateData['picture']['title'] = $title;
        }

        if (null !== $this->floatingProperty) {
            $templateData['floatClass'] = " float_{$this->floatingProperty}";
        }

        // Link and lightbox attributes
        if (!empty($attributes = $this->getAttributes())) {
            $htmlAttributes = array_map(
                static function (string $attribute, string $value) {
                    return sprintf('%s="%s"', $attribute, $value);
                },
                array_keys($attributes), $attributes
            );

            $templateData['attributes'] = ' '.implode(' ', $htmlAttributes);
        }

        // Link target
        if (null !== ($href = $this->getHref())) {
            $templateData['href'] = $href;
        }

        // Secondary image
        if (null !== $this->secondaryStudio) {
            if (!empty($templateData['imageTitle']) && empty($templateData['linkTitle'])) {
                $templateData['linkTitle'] = $templateData['imageTitle'];
                unset($templateData['imageTitle']);
            }

            $templateData['lightboxPicture'] = [
                'img' => $this->secondaryStudio->getImg(),
                'sources' => $this->secondaryStudio->getSources(),
            ];
        }

        return $templateData;
    }

    /**
     * Apply the template data to an existing template. This handles special
     * cases to prevent overriding existing keys / values.
     */
    public function applyToTemplate(Template $template): void
    {
        $new = $this->getData();
        $existing = $template->getData();

        // Do not override the "href" key (see #6468)
        if (isset($new['href'], $existing['href'])) {
            $new['imageHref'] = $new['href'];
            unset($new['href']);
        }

        // Append attributes instead of replacing
        if (isset($new['attributes'], $existing['attributes'])) {
            $new['attributes'] = ($existing['attributes'] ?? '').$new['attributes'];
        }

        $template->setData(array_replace_recursive($existing, $new));
    }

    private function getFallbackLightBoxId(): string
    {
        // Try to generate a unique identifier that is stable across calls
        $identifier = $this->studio->getMetaData()->getUrl() ?? $this->studio->getFilePath();

        return substr(md5($identifier), 0, 6);
    }
}
