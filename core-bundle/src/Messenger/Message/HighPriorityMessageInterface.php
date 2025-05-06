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

trigger_deprecation('contao/core-bundle', '5.6', 'The HighPriorityMessageInterface is deprecated, use the #AsMessage(\'contao_prio_high\') attribute instead.');

/**
 * @deprecated the HighPriorityMessageInterface is deprecated, use the #AsMessage('contao_prio_high') attribute instead
 */
interface HighPriorityMessageInterface
{
}
