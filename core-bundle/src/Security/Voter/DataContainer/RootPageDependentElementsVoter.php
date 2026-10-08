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

use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Root page dependent elements can only be created in themes.
 *
 * @internal
 */
class RootPageDependentElementsVoter extends AbstractDataContainerVoter
{
    protected function getTable(): string
    {
        return 'tl_content';
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        if ($action instanceof ReadAction || $action instanceof DeleteAction) {
            return true;
        }

        $record = array_replace($action instanceof UpdateAction ? $action->getCurrent() : [], $action->getNew() ?? []);

        return 'root_page_dependent_elements' !== ($record['type'] ?? null) || 'tl_theme' === ($record['ptable'] ?? null);
    }
}
