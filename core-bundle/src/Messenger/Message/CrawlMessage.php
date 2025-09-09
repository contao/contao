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

use Symfony\Component\Messenger\Attribute\AsMessage;

/**
 * @internal
 */
#[AsMessage('contao_prio_low')]
class CrawlMessage
{
    public function __construct(
        public string $jobUuid,
        public array $subscribers,
        public int $maxDepth,
        public array $headers,
    ) {
    }
}
