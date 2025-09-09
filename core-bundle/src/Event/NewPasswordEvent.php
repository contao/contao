<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event;

use Contao\MemberModel;
use Symfony\Contracts\EventDispatcher\Event;

class NewPasswordEvent extends Event
{
    public function __construct(
        private readonly MemberModel $member,
        private readonly string $password,
        private readonly string $hashedPassword,
    ) {
    }

    public function getMember(): MemberModel
    {
        return $this->member;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getHashedPassword(): string
    {
        return $this->hashedPassword;
    }
}
