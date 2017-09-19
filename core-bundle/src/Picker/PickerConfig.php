<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Picker;

/**
 * Picker configuration.
 */
class PickerConfig implements \JsonSerializable
{
    /**
     * @var string
     */
    private $context;

    /**
     * @var array
     */
    private $extras = [];

    /**
     * @var string
     */
    private $value;

    /**
     * @var string
     */
    private $current;

    /**
     * Constructor.
     *
     * @param string     $context
     * @param array      $extras
     * @param string|int $value
     * @param string     $current
     */
    public function __construct(string $context, array $extras = [], $value = '', string $current = '')
    {
        $this->context = $context;
        $this->extras = $extras;
        $this->value = $value;
        $this->current = $current;
    }

    /**
     * Returns the context.
     *
     * @return string
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * Returns the extras.
     *
     * @return array
     */
    public function getExtras(): array
    {
        return $this->extras;
    }

    /**
     * Returns the value.
     *
     * @return string
     */
    public function getValue(): string
    {
        return (string) $this->value;
    }

    /**
     * Returns the alias of the current picker.
     *
     * @return string
     */
    public function getCurrent(): string
    {
        return $this->current;
    }

    /**
     * Returns an extra by name.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getExtra(string $name)
    {
        return $this->extras[$name] ?? null;
    }

    /**
     * Sets an extra.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function setExtra(string $name, $value): void
    {
        $this->extras[$name] = $value;
    }

    /**
     * Duplicates the configuration and overrides the current picker alias.
     *
     * @param string $current
     *
     * @return PickerConfig
     */
    public function cloneForCurrent(string $current): PickerConfig
    {
        return new self($this->context, $this->extras, $this->value, $current);
    }

    /**
     * {@inheritdoc}
     */
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
     *
     * @return string
     */
    public function urlEncode(): string
    {
        $data = json_encode($this);

        if (function_exists('gzencode') && false !== ($encoded = @gzencode($data))) {
            $data = $encoded;
        }

        return strtr(base64_encode($data), '+/=', '-_,');
    }

    /**
     * Initializes the object from the URL data.
     *
     * @param string $data
     *
     * @throws \InvalidArgumentException
     *
     * @return PickerConfig
     */
    public static function urlDecode(string $data): PickerConfig
    {
        $data = base64_decode(strtr($data, '-_,', '+/='), true);

        if (function_exists('gzdecode') && false !== ($uncompressed = @gzdecode($data))) {
            $data = $uncompressed;
        }

        $data = @json_decode($data, true);

        if (null === $data) {
            throw new \InvalidArgumentException('Invalid JSON data');
        }

        return new self($data['context'], $data['extras'], $data['value'], $data['current']);
    }
}
