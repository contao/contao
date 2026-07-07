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

enum DatabaseMigrationDecision: string
{
    case Abort = 'abort';
    case Continue = 'continue';
    case Execute = 'execute';
    case Skip = 'skip';
    case WithoutDeletes = 'without-deletes';
    case WithDeletes = 'with-deletes';
}
