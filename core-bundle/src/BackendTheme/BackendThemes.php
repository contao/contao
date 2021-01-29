<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\BackendTheme;

/**
 * This class holds all installed backend themes that can be fetched by their alias.
 *
 * @internal
 */
class BackendThemes
{
    private array $themes;

    public function __construct(iterable $themes)
    {
        $themes = iterator_to_array($themes);

        $this->themes = $themes;
    }

    public function addTheme(BackendThemeInterface $backendTheme, string $alias): void
    {
        $this->themes[$alias] = $backendTheme;
    }

    public function getThemeNames(): array
    {
        return array_keys($this->themes);
    }

    public function getTheme(string $name): ?BackendThemeInterface
    {
        if (!\array_key_exists($name, $this->themes)) {
            return null;
        }

        return $this->themes[$name];
    }
}
