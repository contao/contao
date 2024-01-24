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

use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;

class MemberGroupsListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(): array
    {
        $options = [-1 => $this->translator->trans('MSC.guests', [], 'contao_default')];
        $groups = $this->connection->fetchAllAssociative('SELECT id, name FROM tl_member_group WHERE tstamp>0 ORDER BY name');

        foreach ($groups as $group) {
            $options[$group['id']] = $group['name'];
        }

        return $options;
    }
}
