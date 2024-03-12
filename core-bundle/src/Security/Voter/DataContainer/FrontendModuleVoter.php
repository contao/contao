<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Security\Voter\DataContainer;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

/**
 * @internal
 */
class FrontendModuleVoter extends AbstractDataContainerVoter
{
    use TypeAccessTrait;

    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisionManager)
    {
    }

    protected function getTable(): string
    {
        return 'tl_module';
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        if (
            !$this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'themes')
            || !$this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_ACCESS_FRONTEND_MODULES])
        ) {
            return false;
        }

        if ($action instanceof ReadAction) {
            return true;
        }

        return $this->hasAccessToType($token, ContaoCorePermissions::USER_CAN_ACCESS_FRONTEND_MODULE_TYPE, $action);
    }
}
