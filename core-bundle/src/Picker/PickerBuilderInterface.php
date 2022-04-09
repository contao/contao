<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Picker;

interface PickerBuilderInterface
{
    /**
     * Returns a picker or null if the context is not supported.
     */
    public function create(PickerConfig $config): PickerInterface|null;

    /**
     * Returns a picker object from encoded URL data.
     */
    public function createFromData(string $data): PickerInterface|null;

    /**
     * Returns whether the given context is supported.
     */
    public function supportsContext(string $context, array $allowed = null): bool;

    /**
     * Returns the picker URL for the given context and configuration.
     */
    public function getUrl(string $context, array $extras = [], string $value = ''): string;
}
