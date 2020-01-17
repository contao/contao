<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
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
     * @param Response        $response
     * @param \Exception|null $previous
     */
    public function __construct(Response $response, \Exception $previous = null)
    {
        $this->response = $response;

        parent::__construct('This exception has no message. Use $exception->getResponse() instead.', 0, $previous);
    }

    /**
     * Returns the response object.
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
