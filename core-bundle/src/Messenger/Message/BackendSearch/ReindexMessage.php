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

use Contao\CoreBundle\Messenger\Message\ScopeAwareMessageInterface;
use Contao\CoreBundle\Messenger\Message\ScopeAwareMessageTrait;
use Contao\CoreBundle\Search\Backend\ReindexConfig;
use Symfony\Component\Messenger\Attribute\AsMessage;

/**
 * @experimental
 */
#[AsMessage('contao_prio_low')]
class ReindexMessage implements ScopeAwareMessageInterface
{
    use ScopeAwareMessageTrait;

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
