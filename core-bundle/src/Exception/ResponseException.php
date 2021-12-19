<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class ResponseException extends \RuntimeException
{
    private Response $response;

    public function __construct(Response $response, \Exception $previous = null)
    {
        $this->response = $response;

        parent::__construct('This exception has no message. Use $exception->getResponse() instead.', 0, $previous);
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
