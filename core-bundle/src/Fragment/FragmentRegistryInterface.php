<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Fragment;

interface FragmentRegistryInterface
{
    public const CONTENT_ELEMENT_FRAGMENT = 'contao.fragment.content_element';
    public const FRONTEND_MODULE_FRAGMENT = 'contao.fragment.frontend_module';
    public const PAGE_TYPE_FRAGMENT = 'contao.fragment.page_type';

    public const CONTENT_ELEMENT_RENDERER = 'contao.fragment.content_element_renderer';
    public const FRONTEND_MODULE_RENDERER = 'contao.fragment.frontend_module_renderer';
    public const PAGE_TYPE_RENDERER = 'contao.fragment.page_type_renderer';

    /**
     * Adds a fragment.
     *
     * The $options array must contain at least the following three keys:
     *
     *     - tag (which contains the fragment tag, e.g. "contao.fragment.frontend_module")
     *     - type (which contains the type within that fragment type, e.g. "navigation")
     *     - controller (which contains the controller reference to that fragment)
     *
     * If a fragment with the same identifier already exists, it will be overwritten.
     *
     * @param string $identifier
     * @param object $fragment
     * @param array  $options
     *
     * @return FragmentRegistryInterface
     */
    public function addFragment(string $identifier, $fragment, array $options): FragmentRegistryInterface;

    /**
     * Returns a fragment by its identifier.
     *
     * @param string $identifier
     *
     * @return object|null
     */
    public function getFragment(string $identifier);

    /**
     * Returns an array of fragments that optionally match a given filter callable,
     * which receives the identifier the fragment instance as arguments.
     *
     * @param callable|null $filter
     *
     * @return object[]
     */
    public function getFragments(callable $filter = null): array;

    /**
     * Returns the fragment options.
     *
     * @param string $identifier
     *
     * @return array
     */
    public function getOptions(string $identifier): array;
}
