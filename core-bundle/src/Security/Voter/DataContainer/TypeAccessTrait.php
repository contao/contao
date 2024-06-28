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
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

trait TypeAccessTrait
{
    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisionManager)
    {
    }

    private function hasAccessToType(TokenInterface $token, string $attribute, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        $types = [];

        if (!$action instanceof CreateAction && isset($action->getCurrent()['type'])) {
            $types[] = $action->getCurrent()['type'];
        }

        if (!$action instanceof DeleteAction && !$action instanceof ReadAction && isset($action->getNew()['type'])) {
            $types[] = $action->getNew()['type'];
        }

        $types = array_unique($types);

        if ([] === $types) {
            return true;
        }

        foreach ($types as $type) {
            if (!$this->accessDecisionManager->decide($token, [$attribute], $type)) {
                return false;
            }
        }

        return true;
    }
}
