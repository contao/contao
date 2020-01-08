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

class UnknownPageTypeException extends \InvalidArgumentException
{
    public static function ofType(string $type) : self
    {
        return new self(sprintf('Page type "%s" is not known.', $type));
    }
}
