<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\OptIn;

class OptInTokenAlreadyConfirmedException extends \RuntimeException
{
    public function __construct(\Exception|null $previous = null)
    {
        parent::__construct('The token has already been confirmed', 0, $previous);
    }
}
