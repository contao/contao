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
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @internal
 */
class FormFieldAccessVoter extends AbstractDataContainerVoter
{
    use ParentAccessTrait;

    protected function getTable(): string
    {
        return 'tl_form_field';
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        return $this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'form')
            && $this->hasAccessToFieldType($token, $action)
            && $this->hasAccessToParent($token, ContaoCorePermissions::USER_CAN_EDIT_FORM, $action);
    }

    private function hasAccessToFieldType(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        if ($action instanceof ReadAction) {
            return true;
        }

        // Checks for field type of new record is a record is being duplicated. If no
        // type is set, NULL will check if any field type is allowed to be created.
        if ($action instanceof CreateAction) {
            return $this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_ACCESS_FIELD_TYPE], $action->getNew()['type'] ?? null);
        }

        // If a record is being updated (on submit), check if the new type is allowed.
        if ($action instanceof UpdateAction && !empty($action->getNew()['type'])) {
            if (!$this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_ACCESS_FIELD_TYPE], $action->getNew()['type'])) {
                return false;
            }

            // Do not check isGrated for the current type again if it's the same as new type
            // that already is allowed.
            if ($action->getNew()['type'] === $action->getCurrent()['type']) {
                return true;
            }
        }

        return $this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_ACCESS_FIELD_TYPE], $action->getCurrent()['type']);
    }
}
