<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Inheritance;

/**
 * @experimental
 */
interface TemplateHierarchyInterface
{
    /**
     * Returns an array [<template identifier> => <path mappings>] where path
     * mappings are arrays [<absolute path> => <template logical name>] in the
     * order they should appear in the inheritance chain for the respective
     * template identifier.
     *
     * If a $themeSlug is given the result will additionally include templates
     * of that theme if there are any.
     *
     * For example:
     *   [
     *     'foo' => [
     *       '/path/to/foo.html.twig' => '@Some/foo.html.twig',
     *       '/other/path/to/foo.html5' => '@Other/foo.html5',
     *     ],
     *   ]
     *
     * @return array<string,array<string, string>>
     */
    public function getInheritanceChains(string|null $themeSlug = null): array;

    /**
     * Finds the next template in the hierarchy and returns the logical name.
     */
    public function getDynamicParent(string $shortNameOrIdentifier, string $sourcePath, string|null $themeSlug = null): string;

    /**
     * Finds the first template in the hierarchy and returns the logical name.
     */
    public function getFirst(string $shortNameOrIdentifier, string|null $themeSlug = null): string;
}
