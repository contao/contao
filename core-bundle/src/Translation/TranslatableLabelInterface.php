<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Translation;

use Symfony\Component\Translation\TranslatableMessage;

interface TranslatableLabelInterface
{
    public function label(): TranslatableMessage;
}
