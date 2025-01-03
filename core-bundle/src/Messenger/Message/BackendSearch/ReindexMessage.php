<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Messenger\Message\BackendSearch;

use Contao\CoreBundle\Messenger\Message\LowPriorityMessageInterface;
use Contao\CoreBundle\Search\Backend\ReindexConfig;

/**
 * @experimental
 */
class ReindexMessage implements LowPriorityMessageInterface
{
    private readonly array $asArray;

    public function __construct(ReindexConfig $reindexConfig)
    {
        $this->asArray = $reindexConfig->toArray();
    }

    public function getReindexConfig(): ReindexConfig
    {
        return ReindexConfig::fromArray($this->asArray);
    }
}
