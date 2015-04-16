<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * Stores a response object.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ResponseException extends \RuntimeException
{
    /**
     * @var Response
     */
    private $response;

    /**
     * Constructor.
     *
     * @param Response   $response The Response object
     * @param \Exception $previous The previous exception
     */
    public function __construct(Response $response, \Exception $previous = null)
    {
        $this->response = $response;

        parent::__construct($response->getContent(), 0, $previous);
    }

    /**
     * Returns the response object.
     *
     * @return Response The response object
     */
    public function getResponse()
    {
        $this->response->headers->set('X-Status-Code', $this->response->getStatusCode());

        return $this->response;
    }
}
