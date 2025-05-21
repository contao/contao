<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Messenger\Message;

trigger_deprecation('contao/core-bundle', '5.6', "The NormalPriorityMessageInterface is deprecated, use the #AsMessage('contao_prio_normal') attribute instead.");

/**
 * @deprecated the NormalPriorityMessageInterface is deprecated, use the #AsMessage('contao_prio_normal') attribute instead
 */
interface NormalPriorityMessageInterface
{
}
