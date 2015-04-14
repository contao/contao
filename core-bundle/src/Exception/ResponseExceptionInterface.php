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
 * Implement this interface in your exceptions to let Contao automatically render the response.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
interface ResponseExceptionInterface
{
    /**
     * Return the response object
     *
     * @return Response
     */
    public function getResponse();

    /**
     * Returns previous Exception (if any)
     *
     * @link http://php.net/manual/en/exception.getprevious.php
     *
     * @return \Exception the previous Exception if available or null otherwise.
     */
    public function getPrevious();
}
