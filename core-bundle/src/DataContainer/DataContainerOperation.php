<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DataContainer;

use Contao\CoreBundle\String\HtmlAttributes;
use Contao\DataContainer;
use Contao\StringUtil;

/**
 * @implements \ArrayAccess<string, mixed>
 */
class DataContainerOperation implements \ArrayAccess
{
    private array $operation;

    private string|null $url = null;

    private string|null $html = null;

    /**
     * @internal
     */
    public function __construct(
        private readonly string $name,
        array $operation,
        private readonly array|null $record,
        private readonly DataContainer $dataContainer,
    ) {
        $id = null === $record ? null : StringUtil::specialchars(rawurldecode((string) ($record['id'] ?? '')));

        // Dereference pointer to $GLOBALS['TL_LANG']
        $operation = StringUtil::resolveReferences($operation);

        if (isset($operation['label'])) {
            if (\is_array($operation['label'])) {
                $operation['title'] = $id ? \sprintf($operation['label'][1] ?? '', $id) : $operation['label'][1] ?? '';
                $operation['label'] = $operation['label'][0] ?? $name;
            } else {
                $operation['label'] = $operation['title'] = $id ? \sprintf($operation['label'], $id) : $operation['label'];
            }
        } else {
            $operation['label'] = $operation['title'] = $name;
        }

        $attributes = $operation['attributes'] ?? new HtmlAttributes();

        if (\is_string($attributes)) {
            $attributes = new HtmlAttributes(null !== $id ? \sprintf($attributes, $id, $id) : $attributes);
        }

        if (isset($operation['class'])) {
            $attributes->addClass($operation['class']);
        }

        // Add the key as CSS class
        $attributes->addClass($name);

        $operation['attributes'] = $attributes;

        $this->operation = $operation;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->operation[$offset]);
    }

    /**
     * @template T as mixed
     *
     * @param T $offset
     *
     * @return (T is "attributes" ? HtmlAttributes : mixed)
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->operation[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ('attributes' === $offset && \is_string($value)) {
            $value = new HtmlAttributes($value);
        }

        $this->operation[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->operation[$offset]);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRecord(): array|null
    {
        return $this->record;
    }

    public function getDataContainer(): DataContainer
    {
        return $this->dataContainer;
    }

    public function getHtml(): string|null
    {
        return $this->html;
    }

    public function setHtml(string|null $html): self
    {
        $this->html = $html;

        return $this;
    }

    public function getUrl(): string|null
    {
        return $this->url;
    }

    public function setUrl(string|null $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function disable(): void
    {
        unset($this['route'], $this['href']);

        if (isset($this['icon'])) {
            $this['icon'] = str_replace('.svg', '--disabled.svg', $this['icon']);
        }
    }

    public function hide(): void
    {
        $this->setHtml('');
    }
}
