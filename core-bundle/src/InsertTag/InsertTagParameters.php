<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag;

use Symfony\Component\Uid\Uuid;

abstract class InsertTagParameters
{
    /**
     * @param array<array-key,float|int|string|array|self|ParsedSequence> $parameters
     */
    public function __construct(private array $parameters = [])
    {
    }

    abstract public function hasInsertTags(): bool;

    /**
     * @param array-key $key
     */
    public function get(int|string $key): ParsedSequence|self|array|float|int|string
    {
        return $this->parameters[$key] ?? throw new \InvalidArgumentException(sprintf('Parameter "%s" does not exist', $key));
    }

    /**
     * @param array-key $key
     */
    public function has(int|string $key): bool
    {
        return isset($this->parameters[$key]);
    }

    /**
     * @return list<array-key>
     */
    public function keys(): array
    {
        return array_keys($this->parameters);
    }

    public function serialize(): string
    {
        $result = '';
        $nestedTags = [];
        $remainingParams = $this->prepareForSerialization($this->parameters, $nestedTags);

        for ($i = 0; isset($remainingParams[$i]); ++$i) {
            if ($remainingParams[$i] instanceof self) {
                break;
            }

            $result .= '::'.$remainingParams[$i];
            unset($remainingParams[$i]);
        }

        if ($remainingParams) {
            if ('' === $result) {
                $result = '::';
            }
            $result .= '?'.http_build_query($remainingParams, '', '&', PHP_QUERY_RFC3986);
        }

        return str_replace(
            array_keys($nestedTags),
            $nestedTags,
            $result,
        );
    }

    /**
     * @param-out array<string,InsertTag> $nestedTags
     */
    private function prepareForSerialization(array $parameters, array &$nestedTags): array
    {
        foreach ($parameters as $key => $value) {
            if ($value instanceof self) {
                $value = $value->parameters;
            }

            if (\is_array($value)) {
                $parameters[$key] = $this->prepareForSerialization($value, $nestedTags);

                continue;
            }

            if ($value instanceof ParsedSequence) {
                $parameters[$key] = '';

                foreach ($value as $item) {
                    if (!\is_string($item)) {
                        $nestedTags[Uuid::v4()->toBase32()] = $item->serialize();
                        $item = array_key_last($nestedTags);
                    }
                    $parameters[$key] .= $item;
                }

                continue;
            }

            $parameters[$key] = (string) $value;
        }

        return $parameters;
    }
}
