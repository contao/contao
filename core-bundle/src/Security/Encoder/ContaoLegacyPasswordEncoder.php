<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Security\Encoder;

use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * @internal Do not use this class in your code. It will be removed in Contao 5.0.
 */
class ContaoLegacyPasswordEncoder extends BasePasswordEncoder
{
    /**
     * {@inheritdoc}
     */
    public function encodePassword($raw, $salt): string
    {
        if ($this->isPasswordTooLong($raw)) {
            throw new BadCredentialsException('Password too long.');
        }

        return sha1($salt.$raw);
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordValid($encoded, $raw, $salt): bool
    {
        return !$this->isPasswordTooLong($raw)
            && $this->comparePasswords($encoded, $this->encodePassword($raw, $salt))
        ;
    }
}
