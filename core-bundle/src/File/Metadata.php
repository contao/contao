<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\File;

/**
 * This class is as a container for file metadata as typically defined in
 * tl_files/tl_content. Its underlying data structure is a key-value store with
 * added getters/setters for convenience.
 *
 * The data must be stored in a normalized form. It is your responsibility to
 * ensure this is the case when creating an instance of this class. You can use
 * the public class constants as keys for a better DX.
 */
class Metadata
{
    final public const VALUE_ALT = 'alt';

    final public const VALUE_CAPTION = 'caption';

    final public const VALUE_TITLE = 'title';

    final public const VALUE_URL = 'link';

    final public const VALUE_UUID = 'uuid';

    final public const VALUE_LICENSE = 'license';

    /**
     * @param array<string, mixed>      $values          Key-value pairs of metadata
     * @param array<string, array>|null $schemaOrgJsonLd JSON-LD data where the key matches the schema.org type
     */
    public function __construct(
        private readonly array $values,
        private array|null $schemaOrgJsonLd = null,
    ) {
    }

    /**
     * Returns a new metadata representation that also contains the given
     * values. Existing keys will be overwritten.
     *
     * @param array<string, mixed> $values
     */
    public function with(array $values): self
    {
        if (!$values) {
            return $this;
        }

        return new self([...$this->values, ...$values]);
    }

    /**
     * Returns a value or null if the value was not found.
     */
    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }

    public function getAlt(): string
    {
        return $this->values[self::VALUE_ALT] ?? '';
    }

    public function getCaption(): string
    {
        return $this->values[self::VALUE_CAPTION] ?? '';
    }

    public function getTitle(): string
    {
        return $this->values[self::VALUE_TITLE] ?? '';
    }

    public function getUrl(): string
    {
        return $this->values[self::VALUE_URL] ?? '';
    }

    /**
     * Returns a UUID reference in ASCII format or null if not set.
     */
    public function getUuid(): string|null
    {
        return $this->values[self::VALUE_UUID] ?? null;
    }

    public function getLicense(): string
    {
        return $this->values[self::VALUE_LICENSE] ?? '';
    }

    /**
     * Returns true if this container contains a given value, false otherwise.
     */
    public function has(string $key): bool
    {
        return isset($this->values[$key]);
    }

    /**
     * Returns the whole data set as an associative array.
     */
    public function all(): array
    {
        return $this->values;
    }

    /**
     * Returns true if this container holds no values, false otherwise.
     */
    public function empty(): bool
    {
        return !$this->values;
    }

    public function getSchemaOrgData(string|null $type = null): array
    {
        // Lazy initialize
        if (null === $this->schemaOrgJsonLd) {
            $this->extractBasicSchemaOrgData();
        }

        if (null === $type) {
            return $this->schemaOrgJsonLd;
        }

        return $this->schemaOrgJsonLd[$type] ?? [];
    }

    private function extractBasicSchemaOrgData(): void
    {
        if ($this->has(self::VALUE_TITLE)) {
            $this->schemaOrgJsonLd['AudioObject']['name'] = $this->getTitle();
            $this->schemaOrgJsonLd['ImageObject']['name'] = $this->getTitle();
            $this->schemaOrgJsonLd['MediaObject']['name'] = $this->getTitle();
            $this->schemaOrgJsonLd['VideoObject']['name'] = $this->getTitle();
            $this->schemaOrgJsonLd['DigitalDocument']['name'] = $this->getTitle();
            $this->schemaOrgJsonLd['SpreadsheetDigitalDocument']['name'] = $this->getTitle();
        }

        if ($this->has(self::VALUE_CAPTION)) {
            $this->schemaOrgJsonLd['AudioObject']['caption'] = $this->getCaption();
            $this->schemaOrgJsonLd['ImageObject']['caption'] = $this->getCaption();
            $this->schemaOrgJsonLd['MediaObject']['caption'] = $this->getCaption();
            $this->schemaOrgJsonLd['VideoObject']['caption'] = $this->getCaption();
            $this->schemaOrgJsonLd['DigitalDocument']['caption'] = $this->getCaption();
            $this->schemaOrgJsonLd['SpreadsheetDigitalDocument']['caption'] = $this->getCaption();
        }

        if ($this->has(self::VALUE_LICENSE)) {
            $this->schemaOrgJsonLd['AudioObject']['license'] = $this->getLicense();
            $this->schemaOrgJsonLd['ImageObject']['license'] = $this->getLicense();
            $this->schemaOrgJsonLd['MediaObject']['license'] = $this->getLicense();
            $this->schemaOrgJsonLd['VideoObject']['license'] = $this->getLicense();
            $this->schemaOrgJsonLd['DigitalDocument']['license'] = $this->getLicense();
            $this->schemaOrgJsonLd['SpreadsheetDigitalDocument']['license'] = $this->getLicense();
        }
    }
}
