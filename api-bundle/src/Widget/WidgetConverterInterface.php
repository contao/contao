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

/**
 * Provide API support for a backend widget without modifying the widget itself.
 */
interface WidgetConverterInterface
{
    /**
     * Whether this converter supports the DCA field configuration.
     */
    public function supports(array $config): bool;

    /**
     * Describe the JSON representation, refining the schema inferred from the DCA.
     */
    public function getSchema(array $config, array $schema): array;

    /**
     * Convert the complete stored field value to its documented JSON representation.
     */
    public function convertToApiValue(mixed $value, array $config, array $schema): mixed;

    /**
     * Prepare an API value for form submission, never for direct storage. Widget
     * validation and data container save callbacks still process the result.
     */
    public function convertToFormValue(mixed $value, array $config, array $schema): mixed;
}
