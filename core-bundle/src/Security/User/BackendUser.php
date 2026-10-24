<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\User;

class BackendUser extends \Contao\BackendUser
{
    public function __construct(array $data = [], array $roles = ['ROLE_USER'])
    {
        parent::__construct();

        $this->arrData = $data;
        $this->intId = $data['id'] ?? 0;
        $this->roles = $roles;
    }

    /**
     * Override the parent method to return the roles passed to the constructor.
     */
    public function getRoles(): array
    {
        return $this->roles;
    }
}
