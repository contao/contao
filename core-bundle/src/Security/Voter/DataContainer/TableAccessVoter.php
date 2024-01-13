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
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\DataContainer;
use Contao\DC_File;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;

/**
 * @internal
 */
class TableAccessVoter implements CacheableVoterInterface
{
    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisionManager)
    {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return str_starts_with($attribute, ContaoCorePermissions::DC_PREFIX);
    }

    public function supportsType(string $subjectType): bool
    {
        return match ($subjectType) {
            CreateAction::class,
            UpdateAction::class => true,
            default => false,
        };
    }

    /**
     * @param CreateAction|UpdateAction $subject
     */
    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute) || DC_File::class === DataContainer::getDriverForTable($subject->getDataSource())) {
                continue;
            }

            $hasNotExcluded = false;

            // Intentionally do not load DCA, it should already be loaded. If DCA is not loaded,
            // the voter just always abstains because it can't decide.
            foreach ($GLOBALS['TL_DCA'][$subject->getDataSource()]['fields'] ?? [] as $config) {
                if (!($config['exclude'] ?? true)) {
                    $hasNotExcluded = true;
                    break;
                }
            }

            if (!$hasNotExcluded && !$this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE], $subject->getDataSource())) {
                return self::ACCESS_DENIED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
