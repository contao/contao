<?php

namespace Contao\Fixtures;

use Symfony\Component\Security\Core\User\UserInterface;

class BackendUser extends \Contao\User implements UserInterface
{
    const SECURITY_SESSION_KEY = '_security_contao_backend';

    public $isAdmin = true;

    public static function getInstance()
    {
        return new self();
    }

    public function authenticate()
    {
        return true;
    }

    public function setUserFromDb()
    {
        // ignore
    }

    public static function loadUserByUsername($username)
    {
        // ignore
    }

    public function getRoles()
    {
        // ignore
    }

    public function getPassword()
    {
        // ignore
    }

    public function getSalt()
    {
        // ignore
    }

    public function getUsername()
    {
        // ignore
    }

    public function eraseCredentials()
    {
        // ignore
    }
}
