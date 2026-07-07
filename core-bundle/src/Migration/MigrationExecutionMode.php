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

enum MigrationExecutionMode: string
{
    case Skip = 'skip';
    case Execute = 'execute';
    case Ask = 'ask';
}
