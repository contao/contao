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

class FrontendUser extends \Contao\FrontendUser
{
    public function __construct(array $data = [], array $roles = ['ROLE_MEMBER'])
    {
        parent::__construct();

        $this->arrData = $data;
        $this->intId = $data['id'] ?? 0;
        $this->arrGroups = $data['groups'];
        $this->roles = $roles;
    }

    /**
     * @return array<int>
     */
    public function getActiveGroups(): array
    {
        return $this->arrData['groups'] ?? [];
    }

    /**
     * @param array<int> $ids
     */
    public function setActiveGroups(array $ids): self
    {
        $this->arrData['groups'] = $ids;

        return $this;
    }

    public function getLoginPage(): int
    {
        return (int) $this->strLoginPage;
    }

    public function setLoginPage(int $pageId): self
    {
        // strLoginPage can be replaced when the parent class is obsolete
        $this->strLoginPage = $pageId;

        return $this;
    }
}
