<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration;

enum WarningMode: string
{
    case Abort = 'abort';
    case Continue = 'continue';
    case Ask = 'ask';
}
