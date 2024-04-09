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

enum AlgorithmConfig: string
{
    case ALGORITHM_SHA_256 = 'SHA-256';
    case ALGORITHM_SHA_384 = 'SHA-384';
    case ALGORITHM_SHA_512 = 'SHA-512';
}
