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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\Database;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
class PagePermissionVoter implements VoterInterface, CacheableVoterInterface, ResetInterface
{
    private array $pagemountsCache = [];

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return \in_array($attribute, [ContaoCorePermissions::DC_PREFIX.'tl_page', ContaoCorePermissions::DC_PREFIX.'tl_article'], true);
    }

    public function supportsType(string $subjectType): bool
    {
        return \in_array($subjectType, [CreateAction::class, ReadAction::class, UpdateAction::class, DeleteAction::class], true);
    }

    public function reset(): void
    {
        $this->pagemountsCache = [];
    }

    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
                return self::ACCESS_ABSTAIN;
            }

            $isGranted = match (true) {
                $subject instanceof CreateAction => $this->canCreate($subject, $token),
                $subject instanceof ReadAction => $this->canRead($subject, $token),
                $subject instanceof UpdateAction => $this->canUpdate($subject, $token),
                $subject instanceof DeleteAction => $this->canDelete($subject, $token),
                default => null,
            };

            if (false === $isGranted) {
                return self::ACCESS_DENIED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }

    private function canCreate(CreateAction $action, TokenInterface $token): bool
    {
        // The copy operation is allowed if either hierarchy or edit is allowed.
        if (null !== $action->getNew() && null === ($action->getNew()['sorting'] ?? null)) {
            $pageId = match ($action->getDataSource()) {
                'tl_page' => (int) $action->getNewId(),
                'tl_article' => (int) $action->getNewPid(),
                default => throw new \UnexpectedValueException(sprintf('Unsupported data source "%s"', $action->getDataSource())),
            };

            return ($this->canEdit($action, $token, $pageId) || $this->canChangeHierarchy($action, $token, $pageId))
                && $this->canAccessPage($token, $pageId)
                && $this->canCreate(new CreateAction($action->getDataSource()), $token);
        }

        // Check access to any page on `create` operation
        if (null === $action->getNewPid()) {
            $pageIds = $this->getPagemounts($token);
        } else {
            $pageIds = [(int) $action->getNewPid()];
        }

        // To actually create a record, both hierarchy and edit permissions must be available.
        foreach ($pageIds as $pageId) {
            if (
                $this->canEdit($action, $token, $pageId)
                && $this->canChangeHierarchy($action, $token, $pageId)
                && $this->canAccessPage($token, $pageId)
            ) {
                return true;
            }
        }

        return false;
    }

    private function canRead(ReadAction $action, TokenInterface $token): bool
    {
        return $this->canAccessPage($token, $this->getCurrentPageId($action));
    }

    private function canUpdate(UpdateAction $action, TokenInterface $token): bool
    {
        $pageId = $this->getCurrentPageId($action);
        $newRecord = $action->getNew();

        // Edit operation
        if (null === $newRecord) {
            return $this->canEdit($action, $token, $pageId)
                && $this->canAccessPage($token, $pageId);
        }

        // Move existing record
        $changeSorting = \array_key_exists('sorting', $newRecord);
        $changePid = \array_key_exists('pid', $newRecord) && $action->getCurrentPid() !== $action->getNewPid();

        if (
            ($changeSorting || $changePid)
            && (
                !$this->canChangeHierarchy($action, $token, $pageId)
                || !$this->canAccessPage($token, $pageId)
            )
        ) {
            return false;
        }

        if (
            $changePid
            && (
                !$this->canChangeHierarchy($action, $token, (int) $action->getNewPid())
                || !$this->canAccessPage($token, (int) $action->getNewPid())
            )
        ) {
            return false;
        }

        unset($newRecord['pid'], $newRecord['sorting'], $newRecord['tstamp']);

        // Record was possibly only moved (pid, sorting), no need to check edit permissions
        if ([] === array_diff($newRecord, $action->getCurrent())) {
            return true;
        }

        return $this->canEdit($action, $token, $pageId)
            && $this->canAccessPage($token, $pageId);
    }

    private function canDelete(DeleteAction $action, TokenInterface $token): bool
    {
        $permission = match ($action->getDataSource()) {
            'tl_page' => ContaoCorePermissions::USER_CAN_DELETE_PAGE,
            'tl_article' => ContaoCorePermissions::USER_CAN_DELETE_ARTICLES,
            default => throw new \UnexpectedValueException('Unsupported data source "'.$action->getDataSource().'"'),
        };

        return $this->accessDecisionManager->decide($token, [$permission], $this->getCurrentPageId($action));
    }

    private function getPagemounts(TokenInterface $token): array
    {
        $user = $token->getUser();

        if (!$user instanceof BackendUser) {
            return [];
        }

        if (isset($this->pagemountsCache[$user->id])) {
            return $this->pagemountsCache[$user->id];
        }

        $database = $this->framework->createInstance(Database::class);

        return $this->pagemountsCache[$user->id] = $database->getChildRecords($user->pagemounts, 'tl_page', false, $user->pagemounts);
    }

    private function getCurrentPageId(DeleteAction|ReadAction|UpdateAction $action): int
    {
        return match ($action->getDataSource()) {
            'tl_page' => (int) $action->getCurrentId(),
            'tl_article' => (int) $action->getCurrentPid(),
            default => throw new \UnexpectedValueException('Unsupported data source "'.$action->getDataSource().'"'),
        };
    }

    private function canEdit(CreateAction|UpdateAction $action, TokenInterface $token, int $pageId): bool
    {
        $attributes = match ($action->getDataSource()) {
            'tl_page' => [ContaoCorePermissions::USER_CAN_EDIT_PAGE],
            'tl_article' => [ContaoCorePermissions::USER_CAN_EDIT_ARTICLES],
            default => throw new \UnexpectedValueException('Unsupported data source "'.$action->getDataSource().'"'),
        };

        return $this->accessDecisionManager->decide($token, $attributes, $pageId);
    }

    private function canChangeHierarchy(CreateAction|UpdateAction $action, TokenInterface $token, int $pageId): bool
    {
        $attributes = match ($action->getDataSource()) {
            'tl_page' => [ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY],
            'tl_article' => [ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY],
            default => throw new \UnexpectedValueException('Unsupported data source "'.$action->getDataSource().'"'),
        };

        return $this->accessDecisionManager->decide($token, $attributes, $pageId);
    }

    private function canAccessPage(TokenInterface $token, int $pageId): bool
    {
        return $this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_ACCESS_PAGE], $pageId);
    }
}
