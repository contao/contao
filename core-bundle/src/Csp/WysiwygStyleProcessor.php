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
    /**
     * @param array<string, string> $allowedCssProperties An array containing the CSS properties as keys and their regex
     *                                                    for the value validation
     */
    public function __construct(private readonly array $allowedCssProperties)
    {
    }

    public function extractStyles(string $htmlFragment): array
    {
        preg_match_all('/ style="([^\"]+)"/i', $htmlFragment, $matches);

        if (!\is_array($matches[1])) {
            return [];
        }

        $styles = [];

        foreach ($matches[1] as $style) {
            $style = html_entity_decode($style, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');

            // No need to use a real CSS parser here as the properties and values we want to
            // support for CSP don't require this.
            foreach (explode(';', $style) as $definition) {
                $chunks = explode(':', $definition, 2);
                $property = trim($chunks[0] ?? '');
                $value = trim($chunks[1] ?? '');

                if ('' === $property && '' === $value) {
                    continue;
                }

                if (!isset($this->allowedCssProperties[$property])) {
                    continue 2;
                }

                if (!preg_match(self::prepareRegex($this->allowedCssProperties[$property]), $value)) {
                    continue 2;
                }
            }

            $styles[] = $style;
        }

        return array_values(array_unique($styles));
    }

    public static function prepareRegex(string $regex): string
    {
        return '(^(?:'.$regex.')$)i';
    }
}
