<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * Initializes a response exception with an Ajax compatible redirect response.
 */
class AjaxRedirectResponseException extends ResponseException
{
    /**
     * Constructor.
     *
     * @param string          $location
     * @param int             $status
     * @param \Exception|null $previous
     */
    public function __construct(string $location, int $status = 302, \Exception $previous = null)
    {
        parent::__construct(new Response($location, $status, ['X-Ajax-Location' => $location]), $previous);
    }
}
