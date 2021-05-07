<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext;

class TerminateResponseContextEvent
{
    /**
     * @var ResponseContext
     */
    private $responseContext;

    public function __construct(ResponseContext $responseContext)
    {
        $this->responseContext = $responseContext;
    }

    public function getResponseContext(): ResponseContext
    {
        return $this->responseContext;
    }
}
