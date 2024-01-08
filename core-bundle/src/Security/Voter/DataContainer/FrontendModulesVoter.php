<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Security\Voter\DataContainer;

use Contao\BackendUser;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class FrontendModulesVoter extends AbstractDataContainerVoter
{
    protected function getTable(): string
    {
        return 'tl_module';
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        $user = $token->getUser();

        if (!$user instanceof BackendUser) {
            return false;
        }

        if ($user->isAdmin) {
            return true;
        }

        if (empty($user->frontendModules)) {
            return true;
        }

        return $this->isAllowedModuleType($action, $user);
    }

    private function isAllowedModuleType(CreateAction|DeleteAction|ReadAction|UpdateAction $subject, BackendUser $user): bool
    {
        if ($subject instanceof CreateAction) {
            $type = $subject->getNew()['type'];

            if (null === $type) {
                return true;
            }

            return \in_array($type, $user->frontendModules, true);
        }

        $type = $subject->getCurrent()['type'];

        return \in_array($type, $user->frontendModules, true);
    }
}
