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
 * Initializes a response exception with an empty response.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class NoContentResponseException extends ResponseException
{
    /**
     * Constructor.
     *
     * @param \Exception|null $previous
     */
    public function __construct(\Exception $previous = null)
    {
        parent::__construct(new Response('', 204), $previous);
    }
}
