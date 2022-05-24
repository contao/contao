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

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\DataContainerSubject;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Security;

/**
 * @internal
 */
class TableAccessVoter implements CacheableVoterInterface
{
    public function __construct(private readonly Security $security)
    {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return \in_array($attribute, [
            ContaoCorePermissions::DC_ACTION_CREATE,
            ContaoCorePermissions::DC_ACTION_EDIT,
            ContaoCorePermissions::DC_ACTION_COPY,
        ], true);
    }

    public function supportsType(string $subjectType): bool
    {
        return DataContainerSubject::class === $subjectType;
    }

    /**
     * @param DataContainerSubject $subject
     */
    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            $hasNotExcluded = false;

            foreach (($GLOBALS['TL_DCA'][$subject->table]['fields'] ?? []) as $config) {
                if (!($config['exclude'] ?? false)) {
                    $hasNotExcluded = true;
                    break;
                }
            }

            if (!$hasNotExcluded && !$this->security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE, $subject->table)) {
                return self::ACCESS_DENIED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
