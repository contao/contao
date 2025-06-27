<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DependencyInjection\Attribute;

/**
 * @experimental
 *
 * Service tag to autoconfigure file manager view operations.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class AsOperationForFileManagerView
{
    public function __construct(
        public string|null $name = null,
        public int|null $priority = null,
    ) {
    }
}
