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

use Closure;
use Contao\CoreBundle\File\MetaData;
use Contao\File;
use Contao\StringUtil;
use Contao\Template;

/**
 * A `Figure` holds image and meta data ready to be applied to a (modern)
 * template's context. (If you're using the "old way" you can still use the
 * provided legacy helper methods to manually apply the data to your template).
 *
 * Wherever possible the actual data is only requested/built on access (lazy).
 */
final class Figure
{
    /**
     * @readonly
     *
     * @var ImageResult
     */
    private $image;

    /**
     * @var MetaData|(Closure(self):MetaData|null)|null
     */
    private $metaData;

    /**
     * @var array<string, string|null>|(Closure(self):array<string, string|null>|null
     */
    private $linkAttributes;

    /**
     * @var LightBoxResult|(Closure(self):LightBoxResult|null)|null
     */
    private $lightBox;

    /**
     * Create a figure container.
     *
     * All arguments but the main image result can also be set via a Closure
     * that returns the value instead (lazily evaluated when needed).
     *
     * todo: is there a nicer way to implement 'A|Closure():A'? promise/future wrapper? or 2 separate args?
     *
     * @param ImageResult                                                                $image          Main image
     * @param MetaData|(Closure(self):MetaData|null)|null                                $metaData       Meta data container
     * @param array<string, string|null>|(Closure(self):array<string, string|null>)|null $linkAttributes Link attributes
     * @param LightBoxResult|(Closure(self):LightBoxResult|null)|null                    $lightBox       Light box
     */
    public function __construct(ImageResult $image, $metaData = null, $linkAttributes = null, $lightBox = null)
    {
        $this->image = $image;
        $this->metaData = $metaData;
        $this->linkAttributes = $linkAttributes;
        $this->lightBox = $lightBox;
    }

    /**
     * Return the image result of the main resource.
     */
    public function getImage(): ImageResult
    {
        return $this->image;
    }

    /**
     * Return if a light box result can be obtained.
     */
    public function hasLightBox(): bool
    {
        $this->resolveIfClosure($this->lightBox);

        return $this->lightBox instanceof LightBoxResult;
    }

    /**
     * Return the light box result (if available).
     */
    public function getLightBox(): LightBoxResult
    {
        if (!$this->hasLightBox()) {
            throw new \LogicException('This result container does not include a light box.');
        }

        // Safely return as Closure will be evaluated by this point
        return $this->lightBox;
    }

    public function hasMetaData(): bool
    {
        $this->resolveIfClosure($this->metaData);

        return $this->metaData instanceof MetaData;
    }

    /**
     * Return the main resource's meta data.
     */
    public function getMetaData(): MetaData
    {
        if (!$this->hasMetaData()) {
            throw new \LogicException('This result container does not include meta data.');
        }

        // Safely return as Closure will be evaluated by this point
        return $this->metaData;
    }

    /**
     * Return a key-value list of all link attributes (excluding `href` by default).
     */
    public function getLinkAttributes(bool $excludeHref = true): array
    {
        $this->resolveIfClosure($this->linkAttributes);

        if (null === $this->linkAttributes) {
            $this->linkAttributes = [];
        }

        // Generate href attribute
        if (!isset($this->linkAttributes['href'])) {
            $this->linkAttributes['href'] = (
            function () {
                if ($this->hasLightBox()) {
                    return $this->getLightBox()->getLinkHref();
                }

                if ($this->hasMetaData()) {
                    return $this->getMetaData()->getUrl();
                }

                return '';
            }
            )();
        }

        // Add rel attribute ("noreferrer noopener") to external links
        if (!isset($this->linkAttributes['rel']) && preg_match('#^https?://#', $this->linkAttributes['href'])) {
            $this->linkAttributes['rel'] = 'noreferrer noopener';
        }

        // Add light box attributes
        if (!isset($this->linkAttributes['data-lightbox']) && $this->hasLightBox()) {
            $lightBox = $this->getLightBox();
            $this->linkAttributes['data-lightbox'] = $lightBox->getGroupId();
        }

        // Allow removing attributes by setting them to `null`
        $linkAttributes = array_filter($this->linkAttributes);

        // Optionally strip href attribute
        return $excludeHref ? array_diff_key($linkAttributes, ['href' => null]) : $linkAttributes;
    }

    /**
     * Return the `href` link attribute.
     */
    public function getLinkHref(): string
    {
        return $this->getLinkAttributes(false)['href'] ?? '';
    }

    /**
     * Compile an opinionated data set ready to be applied to a Contao template.
     *
     * Note: Do not use this method when using modern/Twig templates! Instead,
     *       add this object to your template's context and directly access the
     *       specific data you need.
     */
    public function getLegacyTemplateData(bool $includeFullMetaData = true, string $floatingProperty = null, string $marginProperty = null): array
    {
        // Create a key-value list of the meta data and apply some renaming and
        // formatting transformations to fit the legacy templates.
        $createLegacyMetaDataMapping = static function (MetaData $metaData): array {
            if ($metaData->empty()) {
                return [];
            }

            $mapping = $metaData->all();

            // Handle special chars
            foreach ([MetaData::VALUE_ALT, MetaData::VALUE_TITLE, MetaData::VALUE_CAPTION] as $key) {
                if (isset($mapping[$key])) {
                    $mapping[$key] = StringUtil::specialchars($mapping[$key]);
                }
            }

            // Rename certain keys (as used in the Contao templates)
            if (isset($mapping[MetaData::VALUE_TITLE])) {
                $mapping['imageTitle'] = $mapping[MetaData::VALUE_TITLE];
            }

            if (isset($mapping[MetaData::VALUE_URL])) {
                $mapping['imageUrl'] = $mapping[MetaData::VALUE_URL];
            }

            unset($mapping[MetaData::VALUE_TITLE], $mapping[MetaData::VALUE_URL]);

            return $mapping;
        };

        $img = $this->getImage();
        $originalSize = $img->getOriginalDimensions()->getSize();
        $fileInfoImageSize = (new File($img->getImageSrc()))->imageSize;

        $linkAttributes = $this->getLinkAttributes();
        $metaData = $this->hasMetaData() ? $this->getMetaData() : new MetaData([]);

        // Primary image and meta data
        $templateData = array_merge(
            [
                'picture' => [
                    'img' => $img->getImg(),
                    'sources' => $img->getSources(),
                    'alt' => StringUtil::specialchars($metaData->getAlt()),
                ],
                'width' => $originalSize->getWidth(),
                'height' => $originalSize->getHeight(),
                'arrSize' => $fileInfoImageSize,
                'imgSize' => sprintf(' width="%d" height="%d"', $fileInfoImageSize[0], $fileInfoImageSize[1]),
                'singleSRC' => $img->getFilePath(),
                'src' => $img->getImageSrc(),
                'fullsize' => ('_blank' === ($linkAttributes['target'] ?? null)) || $this->hasLightBox(),
                'margin' => $marginProperty ?? '',
                'addBefore' => 'below' !== $floatingProperty,
                'addImage' => true,
                'linkTitle' => '', // always there if not explicitly removed (BC)
            ],
            $includeFullMetaData ? $createLegacyMetaDataMapping($metaData) : []
        );

        // Link attributes
        if ('' !== ($href = $this->getLinkHref())) {
            $templateData['href'] = $href;
            $templateData['attributes'] = '';

            // Set/move link title
            $templateData['linkTitle'] = ($templateData['imageTitle'] ?? null) ?? StringUtil::specialchars($metaData->getTitle());
            unset($templateData['imageTitle']);
        } elseif ($metaData->has(MetaData::VALUE_TITLE)) {
            $templateData['picture']['title'] = StringUtil::specialchars($metaData->getTitle());
        }

        if (!empty($linkAttributes)) {
            $htmlAttributes = array_map(
                static function (string $attribute, string $value) {
                    return sprintf('%s="%s"', $attribute, $value);
                },
                array_keys($linkAttributes), $linkAttributes
            );

            $templateData['attributes'] = ' '.implode(' ', $htmlAttributes);
        }

        // Light box
        if ($this->hasLightBox()) {
            $lightBox = $this->getLightBox();

            if ($lightBox->hasImage()) {
                $image = $lightBox->getImage();

                $templateData['lightboxPicture'] = [
                    'img' => $image->getImg(),
                    'sources' => $image->getSources(),
                ];
            }
        }

        // Other
        if (null !== $floatingProperty) {
            $templateData['floatClass'] = " float_{$floatingProperty}";
        }

        return $templateData;
    }

    /**
     * Apply the legacy template data to an existing Contao template. This
     * handles special cases to prevent overriding existing keys / values.
     *
     * Note: Do not use this method when using modern/Twig templates! Instead,
     *       add this object to your template's context and directly access the
     *       specific data you need.
     */
    public function applyLegacyTemplateData($template, $includeFullMetaData = true, $floatingProperty = null, $marginProperty = null): void
    {
        $new = $this->getLegacyTemplateData($includeFullMetaData, $floatingProperty, $marginProperty);
        $existing = $template instanceof Template ? $template->getData() : get_object_vars($template);

        // Do not override the "href" key (see #6468)
        if (isset($new['href'], $existing['href'])) {
            $new['imageHref'] = $new['href'];
            unset($new['href']);
        }

        // Append attributes instead of replacing
        // todo: where was this from? remove?
        if (isset($new['attributes'], $existing['attributes'])) {
            $new['attributes'] = ($existing['attributes'] ?? '').$new['attributes'];
        }

        // Apply data
        if ($template instanceof Template) {
            $template->setData(array_replace_recursive($existing, $new));

            return;
        }

        foreach ($new as $key => $value) {
            $template->$key = $value;
        }
    }

    /**
     * Evaluate Closure to retrieve the value.
     */
    private function resolveIfClosure(&$property): void
    {
        if ($property instanceof Closure) {
            $property = $property($this);
        }
    }
}
