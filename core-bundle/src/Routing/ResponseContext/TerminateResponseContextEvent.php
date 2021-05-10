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

use Symfony\Component\HttpFoundation\Response;

class TerminateResponseContextEvent
{
    /**
     * @var ResponseContext
     */
    private $responseContext;

    /**
     * @var Response
     */
    private $response;

    public function __construct(ResponseContext $responseContext, Response $response)
    {
        $this->responseContext = $responseContext;
        $this->response = $response;
    }

    public function getResponseContext(): ResponseContext
    {
        return $this->responseContext;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
