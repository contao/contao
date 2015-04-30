<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\RefererId;

use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;

/**
 * Generates an 8 character referer token.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class TokenGenerator extends UriSafeTokenGenerator
{
    /**
     * {@inheritdoc}
     */
    public function generateToken()
    {
        return substr(parent::generateToken(), 0, 8);
    }
}
