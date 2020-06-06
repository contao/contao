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
use Contao\StringUtil;
use Contao\Template;

/**
 * This class is a proxy for template data created by the image studio. It
 * provides a convenient way to access data in a structured form for custom
 * business logic or the use inside Twig (e.g. get HTML attributes as key
 * value pairs) as well as in a preprocessed form ready to use in Contao
 * templates (e.g. get HTML attributes as a rendered string).
 */
final class StudioImage
{
    /**
     * @var Studio
     */
    private $studio;

    /**
     * @var Studio|null
     */
    private $secondaryStudio;

    /**
     * Link attributes in addition to linkHref.
     *
     * @var array<string,string>
     */
    private $linkAttributes = [];

    public function __construct(Studio $studio, array $linkAttributes = [], ?Studio $secondaryStudio = null)
    {
        $this->studio = $studio;
        $this->linkAttributes = $linkAttributes;
        $this->secondaryStudio = $secondaryStudio;
    }

    public function setLinkAttribute(string $attribute, ?string $value): self
    {
        if (null === $value) {
            unset($this->linkAttributes[$attribute]);
        } else {
            $this->linkAttributes[$attribute] = $value;
        }

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

    /**
     * Get the link/light box attributes as key value pairs.
     */
    public function getLinkAttributes(): array
    {
        return $this->linkAttributes;
    }

    /**
     * Get the link uri or null if none exists.
     */
    public function getLinkHref(): ?string
    {
        if (null !== $this->secondaryStudio) {
            return $this->secondaryStudio->getImg()['src'];
        }

        return $this->studio->getMetaData()->getUrl() ?: null;
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
            $this->getNormalizedMetaDataMapping($metaData),
            [
                'picture' => [
                    'img' => $this->getImg(),
                    'sources' => $this->getSources(),
                    'alt' => StringUtil::specialchars($metaData->getAlt()),
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
        if (null !== ($title = StringUtil::specialchars($metaData->getTitle()))) {
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
        if (null !== ($href = $this->getLinkHref())) {
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

    private function getNormalizedMetaDataMapping(MetaData $metaData): array
    {
        $mapping = $metaData->getAll();

        // Handle special chars
        foreach ([MetaData::VALUE_ALT, MetaData::VALUE_TITLE, MetaData::VALUE_LINK_TITLE, MetaData::VALUE_CAPTION] as $key) {
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
    }
}
