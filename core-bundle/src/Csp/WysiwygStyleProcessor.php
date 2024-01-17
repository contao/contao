<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Csp;

class WysiwygStyleProcessor
{
    public function __construct(private readonly array $allowedCssProperties)
    {
    }

    public function extractStyles(string $htmlFragment): array
    {
        // Shortcut for performance reasons
        // TODO: worth it for a regex?
        if (!str_contains($htmlFragment, 'style=')) {
            return [];
        }

        preg_match_all('/ style="([^\"]+)"/m', $htmlFragment, $matches);

        if (!isset($matches[1]) || !\is_array($matches[1])) {
            return [];
        }

        $styles = [];

        foreach ($matches[1] as $style) {
            // TODO: do we need a simple parser here? ; could be within "" or so
            foreach (explode(';', $style) as $definition) {
                $property = trim(explode(':', $definition, 2)[0] ?? '');

                if ('' !== $property && !\in_array($property, $this->allowedCssProperties, true)) {
                    continue 2;
                }
            }

            $styles[] = $style;
        }

        return array_values(array_unique($styles));
    }
}
