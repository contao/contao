<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Fixtures\Enum;

enum IntBackedEnum: int
{
    case optionA = 13;
    case optionB = 42;
}
