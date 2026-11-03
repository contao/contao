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
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\DataContainer;
use Contao\DC_File;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
class TableAccessVoter implements CacheableVoterInterface, ResetInterface
{
    /**
     * @var \WeakMap<TokenInterface, array{access: array<string, bool>, read: array<string, bool>}>
     */
    private \WeakMap $moduleAccessCache;

    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisionManager)
    {
        $this->moduleAccessCache = new \WeakMap();
    }

    public function reset(): void
    {
        $this->moduleAccessCache = new \WeakMap();
    }

    public function supportsAttribute(string $attribute): bool
    {
        return str_starts_with($attribute, ContaoCorePermissions::DC_PREFIX);
    }

    public function supportsType(string $subjectType): bool
    {
        return \in_array($subjectType, [CreateAction::class, ReadAction::class, UpdateAction::class, DeleteAction::class], true);
    }

    /**
     * @param CreateAction|ReadAction|UpdateAction|DeleteAction $subject
     */
    public function vote(TokenInterface $token, $subject, array $attributes, Vote|null $vote = null): int
    {
        if (!array_filter($attributes, $this->supportsAttribute(...))) {
            return self::ACCESS_ABSTAIN;
        }

        // Check access to a module with this DCA
        if (!$this->hasAccessToModule($token, $subject)) {
            return self::ACCESS_DENIED;
        }

        // If table access is granted, we don't check field permission for READ or DELETE
        if ($subject instanceof ReadAction || $subject instanceof DeleteAction) {
            return self::ACCESS_ABSTAIN;
        }

        // DC_File does not have excluded fields
        if (DC_File::class === DataContainer::getDriverForTable($subject->getDataSource())) {
            return self::ACCESS_ABSTAIN;
        }

        $hasNotExcluded = false;

        // Intentionally do not load DCA, it should already be loaded. If DCA is not
        // loaded, the voter just always abstains because it can't decide.
        foreach ($GLOBALS['TL_DCA'][$subject->getDataSource()]['fields'] ?? [] as $config) {
            if (!($config['exclude'] ?? true)) {
                $hasNotExcluded = true;
                break;
            }
        }

        if (!$hasNotExcluded && !$this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE], $subject->getDataSource())) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    private function hasAccessToModule(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $subject): bool
    {
        $table = $subject->getDataSource();
        $cache = $this->moduleAccessCache[$token] ?? ['access' => [], 'read' => []];

        if (isset($cache['access'][$table]) || ($subject instanceof ReadAction && isset($cache['read'][$table]))) {
            return $cache['access'][$table] ?? $cache['read'][$table];
        }

        foreach ($GLOBALS['BE_MOD'] as $modules) {
            foreach ($modules as $name => $config) {
                if (
                    \is_array($config['tables'] ?? null)
                    && \in_array($table, $config['tables'], true)
                    && $this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], $name)
                ) {
                    $cache['access'][$table] = true;
                    $this->moduleAccessCache[$token] = $cache;

                    return true;
                }

                if (
                    $subject instanceof ReadAction
                    && \is_array($config['ptables'] ?? null)
                    && \in_array($table, $config['ptables'], true)
                    && $this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], $name)
                ) {
                    $cache['read'][$table] = true;
                    $this->moduleAccessCache[$token] = $cache;

                    return true;
                }
            }
        }

        $cache['access'][$table] = false;
        $this->moduleAccessCache[$token] = $cache;

        return false;
    }
}
