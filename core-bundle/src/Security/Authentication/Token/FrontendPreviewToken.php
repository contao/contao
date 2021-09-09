<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Authentication\Token;

use Contao\FrontendUser;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class FrontendPreviewToken extends AbstractToken
{
    private bool $showUnpublished;

    public function __construct(?FrontendUser $user, bool $showUnpublished)
    {
        if (null === $user) {
            parent::__construct();
            $this->setUser('anon.');
        } else {
            parent::__construct($user->getRoles());
            $this->setUser($user);
        }

        $this->showUnpublished = $showUnpublished;

        $this->setAuthenticated(true);
    }

    public function __serialize(): array
    {
        return [$this->showUnpublished, parent::__serialize()];
    }

    public function __unserialize(array $data): void
    {
        [$this->showUnpublished, $parentData] = $data;

        parent::__unserialize($parentData);
    }

    public function getCredentials()
    {
        return null;
    }

    public function showUnpublished(): bool
    {
        return $this->showUnpublished;
    }
}
