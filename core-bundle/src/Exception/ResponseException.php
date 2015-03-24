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
 * Exception to send a response and exit the execution in the Contao workflow.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class ResponseException extends \RuntimeException implements ResponseExceptionInterface
{
    /**
     * The response to use.
     *
     * @var Response
     */
    private $response;

    /**
     * Construct the exception.
     *
     * @param Response $response The Response to send
     */
    public function __construct($response)
    {
        parent::__construct('Contao Response', 0);

        $this->response = $response;
    }

    /**
     * Factory method for chain-ability and create the Response instance internally.
     *
     * Example:
     *
     *     throw ResponseException::create($body, 418);
     *
     * @param mixed $content The response content, see setContent()
     * @param int   $status  The response status code
     * @param array $headers An array of response headers
     *
     * @return Response
     */
    public static function create($content = '', $status = 200, $headers = array())
    {
        return new static(new Response($content, $status, $headers));
    }

    /**
     * Retrieve the response.
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
