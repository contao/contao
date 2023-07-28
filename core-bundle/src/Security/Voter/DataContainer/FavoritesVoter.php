<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Voter\DataContainer;

use Contao\BackendUser;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Security;

/**
 * @internal
 */
class FavoritesVoter implements CacheableVoterInterface
{
    public function __construct(private Security $security, private Connection $connection)
    {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === ContaoCorePermissions::DC_PREFIX.'tl_favorites';
    }

    public function supportsType(string $subjectType): bool
    {
        return \in_array($subjectType, [CreateAction::class, ReadAction::class, UpdateAction::class, DeleteAction::class], true);
    }

    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            $isGranted = match (true) {
                $subject instanceof CreateAction => true,
                $subject instanceof ReadAction,
                $subject instanceof UpdateAction,
                $subject instanceof DeleteAction => $this->checkAccess($subject),
                default => false,
            };

            if (!$isGranted) {
                return self::ACCESS_DENIED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }

    private function checkAccess(DeleteAction|ReadAction|UpdateAction $subject): bool
    {
        $user = $this->security->getUser();
        $userId = $user instanceof BackendUser ? (int) $user->id : 0;

        $createdBy = (int) $this->connection->fetchOne(
            'SELECT user FROM tl_favorites WHERE id = :id',
            ['id' => $subject->getCurrentId()]
        );

        return $createdBy === $userId;
    }
}
