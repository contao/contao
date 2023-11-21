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

class NoContentResponseException extends ResponseException
{
    public function __construct(\Exception|null $previous = null)
    {
        parent::__construct(new Response('', 204), $previous);
    }
}
