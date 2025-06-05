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

use Contao\CoreBundle\Translation\TranslatableLabelInterface;
use Symfony\Component\Translation\TranslatableMessage;

enum Status: string implements TranslatableLabelInterface
{
    case NEW = 'new';
    case PENDING = 'pending';
    case FINISHED = 'finished';

    public function label(): TranslatableMessage
    {
        return new TranslatableMessage('tl_job.statusLabel.'.$this->getTranslationKey(), [], 'contao_tl_job');
    }

    private function getTranslationKey(): string
    {
        return match ($this) {
            self::NEW => 'new',
            self::PENDING => 'pending',
            self::FINISHED => 'finished',
        };
    }
}
