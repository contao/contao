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

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Job\Owner;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsCallback(table: 'tl_job', target: 'config.onload')]
class JobsListener
{
    public function __construct(
        private readonly Security $security,
        private readonly Connection $connection,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $userIdentifier = $this->security->getUser()?->getUserIdentifier();

        if (!$request || !$userIdentifier) {
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

        $query = \sprintf('%s AND (owner = %s OR (public = %s AND owner = %s))',
            $pidFilter,
            $this->connection->quote($userIdentifier),
            $this->connection->quote(true, ParameterType::BOOLEAN),
            $this->connection->quote(Owner::SYSTEM),
        );

        $GLOBALS['TL_DCA']['tl_job']['list']['sorting']['filter'][] = $query;
    }
}
