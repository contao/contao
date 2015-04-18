<?php

namespace Contao\Fixtures;

use Contao\User as BaseUser;

class FrontendUser extends BaseUser
{
    public $authenticated = true;

    public static function getInstance()
    {
        return new self();
    }

    public function authenticate()
    {
        return $this->authenticated;
    }

    public function setUserFromDb()
    {
        // do nothing
    }
}
