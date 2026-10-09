<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Webhook\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
class WebhookProperty
{
    public function __construct(
        public readonly string|null $label = null,
        public readonly string|null $description = null,
        public readonly mixed $example = null,
    ) {
    }
}
