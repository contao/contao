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
use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Job\Owner;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class JobsListener
{
    public function __construct(
        private readonly Security $security,
        private readonly Connection $connection,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[AsCallback(table: 'tl_job', target: 'list.operations.children.button')]
    public function onChildrenCallback(DataContainerOperation $operation): void
    {
        $childCount = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM tl_job WHERE pid = ?',
            [(string) $operation->getRecord()['id']],
        );

        if ($childCount < 1) {
            $operation->disable();
        }
    }

    #[AsCallback(table: 'tl_job', target: 'config.onload')]
    public function onLoadCallback(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $userId = $this->getContaoBackendUserId();

        if (!$request || 0 === $userId) {
            return;
        }

        // Job children view
        if ($request->query->has('ptable')) {
            $pidFilter = 'pid != 0';
            $GLOBALS['TL_DCA']['tl_job']['list']['sorting']['mode'] = DataContainer::MODE_PARENT;
            $GLOBALS['TL_DCA']['tl_job']['list']['label']['fields'] = ['uuid', 'status'];
            $GLOBALS['TL_DCA']['tl_job']['list']['label']['format'] = '%s <span class="label-info">%s</span>';
            unset($GLOBALS['TL_DCA']['tl_job']['list']['operations']['children']);
        } else {
            $pidFilter = 'pid = 0';
        }

        $query = \sprintf('%s AND (owner = %d OR (public = %s AND owner = %d))',
            $pidFilter,
            $userId,
            $this->connection->quote(true, ParameterType::BOOLEAN),
            Owner::SYSTEM,
        );

        $GLOBALS['TL_DCA']['tl_job']['list']['sorting']['filter'][] = $query;
    }

    /**
     * @return int 0 if no contao backend user was given
     */
    private function getContaoBackendUserId(): int
    {
        $user = $this->security->getUser();

        if ($user instanceof BackendUser) {
            return (int) $user->id;
        }

        return 0;
    }
}
