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

#[AsMessage('contao_prio_low')]
class ResizeDeferredImageMessage implements ScopeAwareMessageInterface
{
    use ScopeAwareMessageTrait;

    public function __construct(private readonly string $path)
    {
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
