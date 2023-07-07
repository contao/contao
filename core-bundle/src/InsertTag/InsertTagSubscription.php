<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag;

/**
 * @internal
 */
final class InsertTagSubscription
{
    public function __construct(
        public readonly object $service,
        public readonly string $method,
        public readonly string $name,
        public readonly string|null $endTag,
        public readonly bool $resolveNestedTags,
        public readonly bool $asFragment,
    ) {
    }
}
