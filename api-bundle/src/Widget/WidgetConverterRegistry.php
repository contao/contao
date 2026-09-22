<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Widget;

final class WidgetConverterRegistry
{
    /**
     * @param iterable<WidgetConverterInterface> $converters In descending service tag priority
     */
    public function __construct(private readonly iterable $converters)
    {
    }

    public function get(array $config): WidgetConverterInterface|null
    {
        foreach ($this->converters as $converter) {
            if ($converter->supports($config)) {
                return $converter;
            }
        }

        return null;
    }
}
