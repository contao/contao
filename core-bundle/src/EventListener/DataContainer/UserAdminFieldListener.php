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
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Symfony\Bundle\SecurityBundle\Security;

#[AsCallback(table: 'tl_user', target: 'config.onpalette')]
class UserAdminFieldListener
{
    public function __construct(private readonly Security $security)
    {
    }

    public function __invoke(string $palette, DataContainer $dc): string
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            return PaletteManipulator::create()
                ->removeField('admin')
                ->applyToString($palette)
            ;
        }

        $user = $this->security->getUser();

        // Prevent the admin from downgrading their own account
        if ($user instanceof BackendUser && (int) $user->id === (int) $dc->id) {
            return PaletteManipulator::create()
                ->removeField(['admin', 'disable', 'start', 'stop'])
                ->applyToString($palette)
            ;
        }

        return $palette;
    }
}
