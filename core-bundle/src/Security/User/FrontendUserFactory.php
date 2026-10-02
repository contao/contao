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

use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\Date;
use Contao\StringUtil;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

class FrontendUserFactory implements UserFactoryInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly TokenChecker $tokenChecker,
    ) {
    }

    public function create(array $data): FrontendUser
    {
        $data = array_map(static fn ($v) => is_numeric($v) ? $v : StringUtil::deserialize($v), $data);

        // Make sure that groups is an array
        $data['groups'] = (array) ($data['groups'] ?? []);

        $user = new FrontendUser($data);

        // Load active groups
        $groups = $this->fetchGroups($data['groups']);

        $user->setActiveGroups(array_column($groups, 'id'));

        if (($firstGroup = array_first($groups)) && $firstGroup['redirect'] && $firstGroup['jumpTo']) {
            $user->setLoginPage((int) $firstGroup['jumpTo']);
        }

        return $user;
    }

    public function supportsClass(string $className): bool
    {
        return FrontendUser::class === $className;
    }

    public function getTable(): string
    {
        return 'tl_member';
    }

    /**
     * @param array<int|string> $groups
     *
     * @return array<array{id: int, redirect: bool, jumpTo: int}>
     */
    private function fetchGroups(array $groups): array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('id', 'redirect', 'jumpTo')
            ->from('tl_member_group')
            ->where('id IN (:ids)')
            ->setParameter('ids', $groups, ArrayParameterType::INTEGER)
        ;

        if (!$this->tokenChecker->isPreviewMode()) {
            $qb
                ->andWhere('disable=0')
                ->andWhere("(start='' OR start<:time)")
                ->andWhere("(stop='' OR stop>:time)")
                ->setParameter('time', Date::floorToMinute())
            ;
        }

        return $qb->fetchAllAssociative();
    }
}
