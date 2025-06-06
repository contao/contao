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

trigger_deprecation('contao/core-bundle', '5.6', "Using the HighPriorityMessageInterface has been deprecated and will no longer work in Contao 6. Use the #[AsMessage('contao_prio_high')] attribute instead.");

/**
 * @deprecated Deprecated since Contao 5.6, to be removed in Contao 6;
 *             use the #[AsMessage('contao_prio_high')] attribute instead.
 */
interface HighPriorityMessageInterface
{
}
