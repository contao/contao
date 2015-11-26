<?php

namespace Contao\Fixtures;

class FrontendUser extends \Contao\User
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
