<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event;


use Contao\DataContainer;
use Contao\StringUtil;

class DcaButtonConfig implements \ArrayAccess
{
    private string|null $html = null;

    /**
     * @internal
     */
    public function __construct(private readonly string $name, private array $operation, private readonly array $record, private readonly DataContainer $dataContainer)
    {
        $id = StringUtil::specialchars(rawurldecode((string) $record['id']));

        if (isset($operation['label'])) {
            if (\is_array($operation['label'])) {
                $operation['label'] = $operation['label'][0] ?? null;
                $operation['title'] = sprintf($operation['label'][1] ?? '', $id);
            } else {
                $operation['label'] = $operation['title'] = sprintf($operation['label'], $id);
            }
        } else {
            $operation['label'] = $operation['title'] = $name;
        }

        $attributes = !empty($operation['attributes']) ? ' '.ltrim(sprintf($operation['attributes'], $id, $id)) : '';

        // Add the key as CSS class
        if (strpos($attributes, 'class="') !== false) {
            $attributes = str_replace('class="', 'class="'.$name.' ', $attributes);
        } else {
            $attributes = ' class="'.$name.'"'.$attributes;
        }
        $operation['attributes'] = $attributes;

        $this->operation = $operation;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->operation[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->operation[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
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

    public function getRecord(): array
    {
        return $this->record;
    }

    public function getDataContainer(): DataContainer
    {
        return $this->dataContainer;
    }

    public function getHtml(): ?string
    {
        return $this->html;
    }

    public function setHtml(string $html): self
    {
        $this->html = $html;

        return $this;
    }
}
