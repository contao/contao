<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Pow\Altcha\Validator;

use Contao\CoreBundle\Pow\Altcha\Altcha;

class AltchaValidator
{
    public function __construct(
        private readonly Altcha $altcha,
    ) {
    }

    public function validate(string $payload): bool
    {
        return $this->altcha->isValidPayload($payload);
    }
}
