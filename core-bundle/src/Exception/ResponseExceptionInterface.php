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
 * Response exception interface.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
interface ResponseExceptionInterface
{
    /**
     * Returns the response object.
     *
     * @return Response The response object
     */
    public function getResponse();

    /**
     * Returns the previous exception.
     *
     * @return \Exception|null The previous exception or null
     */
    public function getPrevious();
}
