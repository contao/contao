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

use Symfony\Component\HttpFoundation\RedirectResponse;

class RedirectResponseException extends ResponseException
{
    public function __construct(string $location, int $status = 303, \Exception|null $previous = null)
    {
        parent::__construct(new RedirectResponse($location, $status), $previous);
    }
}
