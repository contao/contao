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

use Contao\CoreBundle\Translation\TranslatableLabelInterface;
use Symfony\Component\Translation\TranslatableMessage;

enum TranslatableEnum: string implements TranslatableLabelInterface
{
    case OptionA = 'option_a';
    case OptionB = 'option_b';

    public function label(): TranslatableMessage
    {
        return new TranslatableMessage(
            'foo.'.$this->value,
            [],
            'contao_default',
        );
    }
}
