<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Referer;

use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;

/**
 * Generates an 8 character referer token.
 */
class TokenGenerator extends UriSafeTokenGenerator
{
    /**
     * {@inheritdoc}
     */
    public function generateToken(): string
    {
        return substr(parent::generateToken(), 0, 8);
    }
}
