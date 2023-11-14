<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Referer;

use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;

/**
 * Generates an 8 character referer token.
 */
class TokenGenerator extends UriSafeTokenGenerator
{
    #[\Override]
    public function generateToken(): string
    {
        return substr(parent::generateToken(), 0, 8);
    }
}
