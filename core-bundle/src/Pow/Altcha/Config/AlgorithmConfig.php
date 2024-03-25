<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Pow\Altcha\Config;

class AlgorithmConfig
{
    public const ALGORITHM_SHA_256 = 'SHA-256';

    public const ALGORITHM_SHA_384 = 'SHA-384';

    public const ALGORITHM_SHA_512 = 'SHA-512';

    public const ALGORITHM_ALL = [
        self::ALGORITHM_SHA_256,
        self::ALGORITHM_SHA_384,
        self::ALGORITHM_SHA_512,
    ];
}
