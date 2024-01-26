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

enum StringBackedEnum: string
{
    case optionA = 'option_a';
    case optionB = 'option_b';
}
