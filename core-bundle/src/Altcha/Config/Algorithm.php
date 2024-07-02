<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Altcha\Config;

enum Algorithm: string
{
    case sha256 = 'SHA-256';
    case sha384 = 'SHA-384';
    case sha512 = 'SHA-512';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
