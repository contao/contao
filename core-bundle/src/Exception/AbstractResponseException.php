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
 * Parent class for response exceptions.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
abstract class AbstractResponseException extends \RuntimeException implements ResponseExceptionInterface
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
     * {@inheritdoc}
     */
    public function getResponse()
    {
        return $this->response;
    }
}
