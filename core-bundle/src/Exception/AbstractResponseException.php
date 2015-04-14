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
        parent::__construct('', 0, $previous);

        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse()
    {
        return $this->response;
    }
}
