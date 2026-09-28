<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Dto;

use ApiPlatform\Metadata\ApiProperty;

final class UserTemplateOperation
{
    public function __construct(
        #[ApiProperty(description: 'Template name without the file extension, for example content_element/code.')]
        public string $name,
        #[ApiProperty(description: 'Operation-specific parameters. See the operation description for required fields and confirmation steps.', openapiContext: ['type' => 'object', 'additionalProperties' => true])]
        public array $parameters = [],
    ) {
    }
}
