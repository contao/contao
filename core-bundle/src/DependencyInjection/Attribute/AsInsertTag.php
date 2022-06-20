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

use Contao\CoreBundle\InsertTag\ProcessingMode;

/**
 * An attribute to register an insert tag.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class AsInsertTag
{
    public function __construct(
        public string $name,
        public string|null $endTag = null,
        public ProcessingMode $mode = ProcessingMode::resolved,
        public int $priority = 0,
        public string|null $method = null,
    ) {
        if (\in_array($mode, [ProcessingMode::wrappedParsed, ProcessingMode::wrappedResolved], true)) {
            if (null === $endTag) {
                throw new \InvalidArgumentException('Missing $endTag parameter');
            }
        } elseif (null !== $endTag) {
            throw new \InvalidArgumentException('$endTag parameter is only supported for wrappedParsed or wrappedResolved mode');
        }
    }
}
