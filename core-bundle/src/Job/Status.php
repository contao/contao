<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Job;

enum Status: string
{
    case NEW = 'new';
    case PENDING = 'pending';
    case FINISHED = 'finished';

    public function getTranslationKey(): string
    {
        return match ($this) {
            self::NEW => 'new',
            self::PENDING => 'pending',
            self::FINISHED => 'finished',
        };
    }
}
