<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Altcha extends Constraint
{
    public string $message = 'ERR.altchaVerificationFailed';
}
