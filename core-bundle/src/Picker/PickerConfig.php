<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Picker;

class PickerConfig implements \JsonSerializable
{
    public function __construct(
        private readonly string $context,
        private array $extras = [],
        private readonly int|string $value = '',
        private readonly string $current = '',
    ) {
    }

    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtras(): array
    {
        return $this->extras;
    }

    public function getValue(): string
    {
        return (string) $this->value;
    }

    /**
     * Returns the alias of the current picker.
     */
    public function getCurrent(): string
    {
        return $this->current;
    }

    /**
     * Returns an extra value for a given provider or the general extra value.
     */
    public function getExtraForProvider(string $name, string $provider): mixed
    {
        return $this->extras[$provider][$name] ?? $this->getExtra($name);
    }

    public function getExtra(string $name): mixed
    {
        return $this->extras[$name] ?? null;
    }

    public function setExtra(string $name, mixed $value): void
    {
        $this->extras[$name] = $value;
    }

    /**
     * Duplicates the configuration and overrides the current picker alias.
     */
    public function cloneForCurrent(string $current): self
    {
        return new self($this->context, $this->extras, $this->value, $current);
    }

    public function jsonSerialize(): array
    {
        return [
            'context' => $this->context,
            'extras' => $this->extras,
            'current' => $this->current,
            'value' => $this->value,
        ];
    }

    /**
     * Encodes the picker configuration for the URL.
     */
    public function urlEncode(): string
    {
        $data = json_encode($this, JSON_THROW_ON_ERROR);

        if (\function_exists('gzencode') && false !== ($encoded = @gzencode($data))) {
            $data = $encoded;
        }

        return strtr(base64_encode($data), '+/=', '-_,');
    }

    /**
     * Initializes the object from the URL data.
     *
     * @throws \InvalidArgumentException
     */
    public static function urlDecode(string $data): self
    {
        $decoded = base64_decode(strtr($data, '-_,', '+/='), true);

        if (\function_exists('gzdecode') && false !== ($uncompressed = @gzdecode($decoded))) {
            $decoded = $uncompressed;
        }

        try {
            $json = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new \InvalidArgumentException('Invalid JSON data');
        }

        return new self($json['context'], $json['extras'], $json['value'], $json['current']);
    }
}
