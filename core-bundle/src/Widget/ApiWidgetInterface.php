<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Widget;

/**
 * Opt in to API field discovery and conversion through the backend widget lifecycle.
 */
interface ApiWidgetInterface
{
    /**
     * Describe the JSON representation, refining the schema inferred from the DCA.
     */
    public static function getApiSchema(array $config, array $schema): array;

    /**
     * Convert the complete stored field value to its documented JSON representation.
     */
    public static function convertToApiValue(mixed $value, array $config, array $schema): mixed;

    /**
     * Prepare an API value for form submission, never for direct storage. Widget
     * validation and data container save callbacks still process the result.
     */
    public static function convertToApiFormValue(mixed $value, array $config, array $schema): mixed;
}
