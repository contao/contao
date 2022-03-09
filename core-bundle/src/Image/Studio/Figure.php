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

use Contao\Controller;
use Contao\CoreBundle\File\Metadata;
use Contao\File;
use Contao\StringUtil;
use Contao\Template;

/**
 * A Figure object holds image and metadata ready to be applied to a
 * template's context. If you are using the legacy PHP templates, you can still
 * use the provided legacy helper methods to manually apply the data to them.
 *
 * Wherever possible, the actual data is only requested/built on demand.
 *
 * @final This class will be made final in Contao 5.
 */
class Figure
{
    private ImageResult $image;

    /**
     * @var Metadata|(\Closure(self):Metadata|null)|null
     */
    private $metadata;

    /**
     * @var array<string, string|null>|(\Closure(self):array<string, string|null>)|null
     */
    private $linkAttributes;

    /**
     * @var LightboxResult|(\Closure(self):LightboxResult|null)|null
     */
    private $lightbox;

    /**
     * @var array<string, mixed>|(\Closure(self):array<string, mixed>)|null
     */
    private $options;

    /**
     * Creates a figure container.
     *
     * All arguments but the main image result can also be set via a Closure
     * that only returns the value on demand.
     *
     * @param Metadata|(\Closure(self):Metadata|null)|null                                $metadata       Metadata container
     * @param array<string, string|null>|(\Closure(self):array<string, string|null>)|null $linkAttributes Link attributes
     * @param LightboxResult|(\Closure(self):LightboxResult|null)|null                    $lightbox       Lightbox
     * @param array<string, mixed>|(\Closure(self):array<string, mixed>)|null             $options        Template options
     */
    public function __construct(ImageResult $image, $metadata = null, $linkAttributes = null, $lightbox = null, $options = null)
    {
        $this->image = $image;
        $this->metadata = $metadata;
        $this->linkAttributes = $linkAttributes;
        $this->lightbox = $lightbox;
        $this->options = $options;
    }

    /**
     * Returns the image result of the main resource.
     */
    public function getImage(): ImageResult
    {
        return $this->image;
    }

    /**
     * Returns true if a lightbox result can be obtained.
     */
    public function hasLightbox(): bool
    {
        $this->resolveIfClosure($this->lightbox);

        return $this->lightbox instanceof LightboxResult;
    }

    /**
     * Returns the lightbox result (if available).
     */
    public function getLightbox(): LightboxResult
    {
        if (!$this->hasLightbox()) {
            throw new \LogicException('This result container does not include a lightbox.');
        }

        /** @var LightboxResult */
        return $this->lightbox;
    }

    public function hasMetadata(): bool
    {
        $this->resolveIfClosure($this->metadata);

        return $this->metadata instanceof Metadata;
    }

    /**
     * Returns the main resource's metadata.
     */
    public function getMetadata(): Metadata
    {
        if (!$this->hasMetadata()) {
            throw new \LogicException('This result container does not include metadata.');
        }

        /** @var Metadata */
        return $this->metadata;
    }

    public function getSchemaOrgData(): array
    {
        $imageIdentifier = $this->getImage()->getImageSrc();

        if ($this->hasMetadata() && $this->getMetadata()->has(Metadata::VALUE_UUID)) {
            $imageIdentifier = '#/schema/image/'.$this->getMetadata()->getUuid();
        }

        $jsonLd = [
            '@type' => 'ImageObject',
            'identifier' => $imageIdentifier,
            'contentUrl' => $this->getImage()->getImageSrc(),
        ];

        if (!$this->hasMetadata()) {
            ksort($jsonLd);

            return $jsonLd;
        }

        $jsonLd = array_merge($this->getMetadata()->getSchemaOrgData('ImageObject'), $jsonLd);
        ksort($jsonLd);

        return $jsonLd;
    }

    /**
     * Returns a key-value list of all link attributes. This excludes "href" by
     * default.
     */
    public function getLinkAttributes(bool $includeHref = false): array
    {
        $this->resolveIfClosure($this->linkAttributes);

        if (null === $this->linkAttributes) {
            $this->linkAttributes = [];
        }

        // Generate the href attribute
        if (!\array_key_exists('href', $this->linkAttributes)) {
            $this->linkAttributes['href'] = (
                function () {
                    if ($this->hasLightbox()) {
                        return $this->getLightbox()->getLinkHref();
                    }

                    if ($this->hasMetadata()) {
                        return $this->getMetadata()->getUrl();
                    }

                    return '';
                }
            )();
        }

        // Add rel attribute "noreferrer noopener" to external links
        if (
            !empty($this->linkAttributes['href'])
            && !\array_key_exists('rel', $this->linkAttributes)
            && preg_match('#^https?://#', $this->linkAttributes['href'])
        ) {
            $this->linkAttributes['rel'] = 'noreferrer noopener';
        }

        // Add lightbox attributes
        if (!\array_key_exists('data-lightbox', $this->linkAttributes) && $this->hasLightbox()) {
            $lightbox = $this->getLightbox();
            $this->linkAttributes['data-lightbox'] = $lightbox->getGroupIdentifier();
        }

        // Allow removing attributes by setting them to null
        $linkAttributes = array_filter($this->linkAttributes, static fn ($attribute): bool => null !== $attribute);

        // Optionally strip the href attribute
        return $includeHref ? $linkAttributes : array_diff_key($linkAttributes, ['href' => null]);
    }

    /**
     * Returns the "href" link attribute.
     */
    public function getLinkHref(): string
    {
        return $this->getLinkAttributes(true)['href'] ?? '';
    }

    /**
     * Returns a key-value list of template options.
     */
    public function getOptions(): array
    {
        $this->resolveIfClosure($this->options);

        return $this->options ?? [];
    }

    /**
     * Compiles an opinionated data set to be applied to a Contao template.
     *
     * Note: Do not use this method when building new templates from scratch or
     *       when using Twig templates! Instead, add this object to your
     *       template's context and directly access the specific data you need.
     *
     * @param string|array|null $margin              Set margins that will compose the inline CSS for the "margin" key
     * @param string|null       $floating            Set/determine values for the "float_class" and "addBefore" keys
     * @param bool              $includeFullMetadata Make all metadata available in the first dimension of the returned data set (key-value pairs)
     */
    public function getLegacyTemplateData($margin = null, string $floating = null, bool $includeFullMetadata = true): array
    {
        // Create a key-value list of the metadata and apply some renaming and
        // formatting transformations to fit the legacy templates.
        $createLegacyMetadataMapping = static function (Metadata $metadata): array {
            if ($metadata->empty()) {
                return [];
            }

            $mapping = $metadata->all();

            // Handle special chars
            foreach ([Metadata::VALUE_ALT, Metadata::VALUE_TITLE] as $key) {
                if (isset($mapping[$key])) {
                    $mapping[$key] = StringUtil::specialchars($mapping[$key]);
                }
            }

            // Rename certain keys (as used in the Contao templates)
            if (isset($mapping[Metadata::VALUE_TITLE])) {
                $mapping['imageTitle'] = $mapping[Metadata::VALUE_TITLE];
            }

            if (isset($mapping[Metadata::VALUE_URL])) {
                $mapping['imageUrl'] = $mapping[Metadata::VALUE_URL];
            }

            unset($mapping[Metadata::VALUE_TITLE], $mapping[Metadata::VALUE_URL]);

            return $mapping;
        };

        // Create a CSS margin property from an array or serialized string
        $createMargin = static function ($margin): string {
            if (!$margin) {
                return '';
            }

            $values = array_merge(
                ['top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'unit' => ''],
                StringUtil::deserialize($margin, true)
            );

            return Controller::generateMargin($values);
        };

        $image = $this->getImage();
        $originalSize = $image->getOriginalDimensions()->getSize();
        $fileInfoImageSize = (new File($image->getImageSrc(true)))->imageSize;

        $linkAttributes = $this->getLinkAttributes();
        $metadata = $this->hasMetadata() ? $this->getMetadata() : new Metadata([]);

        // Primary image and metadata
        $templateData = array_merge(
            [
                'picture' => [
                    'img' => $image->getImg(),
                    'sources' => $image->getSources(),
                    'alt' => StringUtil::specialchars($metadata->getAlt()),
                ],
                'width' => $originalSize->getWidth(),
                'height' => $originalSize->getHeight(),
                'arrSize' => $fileInfoImageSize,
                'imgSize' => !empty($fileInfoImageSize) ? sprintf(' width="%d" height="%d"', $fileInfoImageSize[0], $fileInfoImageSize[1]) : '',
                'singleSRC' => $image->getFilePath(),
                'src' => $image->getImageSrc(),
                'fullsize' => ('_blank' === ($linkAttributes['target'] ?? null)) || $this->hasLightbox(),
                'margin' => $createMargin($margin),
                'addBefore' => 'below' !== $floating,
                'addImage' => true,
            ],
            $includeFullMetadata ? $createLegacyMetadataMapping($metadata) : []
        );

        // Link attributes and title
        if ('' !== ($href = $this->getLinkHref())) {
            $templateData['href'] = $href;
            $templateData['attributes'] = ''; // always define attributes key if href is set

            // Use link "title" attribute for "linkTitle" as it is already output explicitly in image.html5 (see #3385)
            if (\array_key_exists('title', $linkAttributes)) {
                $templateData['linkTitle'] = $linkAttributes['title'];
                unset($linkAttributes['title']);
            } else {
                // Map "imageTitle" to "linkTitle"
                $templateData['linkTitle'] = ($templateData['imageTitle'] ?? null) ?? StringUtil::specialchars($metadata->getTitle());
                unset($templateData['imageTitle']);
            }
        } elseif ($metadata->has(Metadata::VALUE_TITLE)) {
            $templateData['picture']['title'] = StringUtil::specialchars($metadata->getTitle());
        }

        if (!empty($linkAttributes)) {
            $htmlAttributes = array_map(
                static fn (string $attribute, string $value) => sprintf('%s="%s"', $attribute, $value),
                array_keys($linkAttributes),
                $linkAttributes
            );

            $templateData['attributes'] = ' '.implode(' ', $htmlAttributes);
        }

        // Lightbox
        if ($this->hasLightbox()) {
            $lightbox = $this->getLightbox();

            if ($lightbox->hasImage()) {
                $lightboxImage = $lightbox->getImage();

                $templateData['lightboxPicture'] = [
                    'img' => $lightboxImage->getImg(),
                    'sources' => $lightboxImage->getSources(),
                ];
            }
        }

        // Other
        if ($floating) {
            $templateData['floatClass'] = " float_$floating";
        }

        // Add arbitrary template options
        return array_merge($templateData, $this->getOptions());
    }

    /**
     * Applies the legacy template data to an existing template. This will
     * prevent overriding the "href" property if already present and use
     * "imageHref" instead.
     *
     * Note: Do not use this method when building new templates from scratch or
     *       when using Twig templates! Instead, add this object to your
     *       template's context and directly access the specific data you need.
     *
     * @param Template|object   $template            The template to apply the data to
     * @param string|array|null $margin              Set margins that will compose the inline CSS for the template's "margin" property
     * @param string|null       $floating            Set/determine values for the template's "float_class" and "addBefore" properties
     * @param bool              $includeFullMetadata Make all metadata entries directly available in the template
     */
    public function applyLegacyTemplateData(object $template, $margin = null, string $floating = null, bool $includeFullMetadata = true): void
    {
        $new = $this->getLegacyTemplateData($margin, $floating, $includeFullMetadata);
        $existing = $template instanceof Template ? $template->getData() : get_object_vars($template);

        // Do not override the "href" key (see #6468)
        if (isset($new['href'], $existing['href'])) {
            $new['imageHref'] = $new['href'];
            unset($new['href']);
        }

        // Allow accessing Figure methods in a legacy template context
        $new['figure'] = $this;

        // Apply data
        if ($template instanceof Template) {
            $template->setData(array_replace($existing, $new));

            return;
        }

        foreach ($new as $key => $value) {
            $template->$key = $value;
        }
    }

    /**
     * Evaluates closures to retrieve the value.
     *
     * @param mixed $property
     */
    private function resolveIfClosure(&$property): void
    {
        if ($property instanceof \Closure) {
            $property = $property($this);
        }
    }
}
