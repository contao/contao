<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event;

use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;

abstract class AbstractResponseContextEvent
{
    private ResponseContext|null $responseContext = null;

    public function setResponseContext(ResponseContext $responseContext): self
    {
        if ($this->responseContext) {
            throw new \LogicException('ResponseContext is already set!');
        }

        $this->responseContext = $responseContext;

        return $this;
    }

    public function getResponseContext(): ResponseContext
    {
        if (!$this->responseContext) {
            throw new \LogicException('ResponseContext must be set!');
        }

        return $this->responseContext;
    }
}
