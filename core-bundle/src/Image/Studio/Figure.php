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
use Contao\StringUtil;
use Contao\Template;

/**
 * A `Figure` holds image and meta data ready to be applied to a (modern)
 * template's context. (If you're using the "old way" you can still use the
 * provided legacy helper methods to manually apply the data to your template).
 *
 * Wherever possible the actual data is only requested/build on access (lazy).
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

    /**
     * Return the main resource's meta data.
     */
    public function getMetaData(): MetaData
    {
        $this->resolveIfClosure($this->metaData);

        if (null === $this->metaData) {
            $this->metaData = new MetaData([]);
        }

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

        // Generate href attribute (on demand)
        if (!isset($this->linkAttributes['href']) && !$excludeHref) {
            $this->linkAttributes['href'] = $this->hasLightBox() ?
                $this->getLightBox()->getLinkHref() : $this->getMetaData()->getUrl();
        }

        // Add light box attributes
        if (!isset($this->linkAttributes['data-lightbox']) && $this->hasLightBox()) {
            $lightBox = $this->getLightBox();
            $this->linkAttributes['data-lightbox'] = $lightBox->getGroupId();

            if (!isset($this->linkAttributes['target']) && !$lightBox->hasImage()) {
                $this->linkAttributes['target'] = '_blank';
            }
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
    public function getLegacyTemplateData($floatingProperty = null, $marginProperty = null): array
    {
        // Create a key-value list of the meta data and apply some renaming and formatting transformations
        $createLegacyMetaDataMapping = static function (MetaData $metaData): array {
            // todo: do we need to make sure all default values are actually
            //       there in case someone unset any? (check template)
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

        $metaData = $this->getMetaData();
        $img = $this->getImage();
        $originalSize = $img->getOriginalDimensions()->getSize();
        $linkAttributes = $this->getLinkAttributes();

        // Primary image and meta data
        $templateData = array_merge(
            $createLegacyMetaDataMapping($metaData),
            [
                'picture' => [
                    'img' => $img->getImg(),
                    'sources' => $img->getSources(),
                    'alt' => StringUtil::specialchars($metaData->getAlt()),
                ],
                'width' => $originalSize->getWidth(),
                'height' => $originalSize->getHeight(),
                'arrSize' => [$originalSize->getWidth(), $originalSize->getHeight()],
                'imgSize' => sprintf(' width="%d" height="%d"', $originalSize->getWidth(), $originalSize->getHeight()),
                'singleSRC' => $img->getFilePath(),
                'fullsize' => ('blank' === $linkAttributes['target'] ?? null) || $this->hasLightBox(),
                'margin' => $marginProperty ?? '',
                'addBefore' => 'below' !== $floatingProperty,
                'addImage' => true,
            ]
        );

        // Context sensitive properties
        if (null !== ($title = StringUtil::specialchars($metaData->getTitle()))) {
            $templateData['picture']['title'] = $title;
        }

        if (null !== $floatingProperty) {
            $templateData['floatClass'] = " float_{$floatingProperty}";
        }

        // Link attributes
        if (!empty($linkAttributes)) {
            $htmlAttributes = array_map(
                static function (string $attribute, string $value) {
                    return sprintf('%s="%s"', $attribute, $value);
                },
                array_keys($linkAttributes), $linkAttributes
            );

            $templateData['attributes'] = ' '.implode(' ', $htmlAttributes);
        }

        if ('' !== ($href = $this->getLinkHref())) {
            $templateData['href'] = $href;
        }

        // Light box
        if ($this->hasLightBox()) {
            if (!empty($templateData['imageTitle']) && empty($templateData['linkTitle'])) {
                $templateData['linkTitle'] = $templateData['imageTitle'];
                unset($templateData['imageTitle']);
            }

            $lightBox = $this->getLightBox();

            if ($lightBox->hasImage()) {
                $image = $lightBox->getImage();

                $templateData['lightboxPicture'] = [
                    'img' => $image->getImg(),
                    'sources' => $image->getSources(),
                ];
            }
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
    public function applyLegacyTemplateData(Template $template, $floatingProperty = null, $marginProperty = null): void
    {
        $new = $this->getLegacyTemplateData($floatingProperty, $marginProperty);
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
