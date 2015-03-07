<?php

namespace Contao\Fixtures;

use Contao\User;

class FrontendUser extends User
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
