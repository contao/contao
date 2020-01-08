<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\PageType;

class InvalidPageAliasException extends \InvalidArgumentException
{
    public static function withInvalidParameters(array $parameters): self
    {
        return new self(
            sprintf('Invalid page alias found. Parameters "%s" are not supported', implode(', ', $parameters))
        );
    }
}
