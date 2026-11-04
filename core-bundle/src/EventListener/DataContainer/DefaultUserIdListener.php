<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\BackendUser;
use Symfony\Bundle\SecurityBundle\Security;

class DefaultUserIdListener
{
    public function __construct(private readonly Security $security)
    {
    }

    public function __invoke(): int
    {
        $user = $this->security->getUser();

        if ($user instanceof BackendUser) {
            return $user->id;
        }

        return 0;
    }
}
