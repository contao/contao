<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Security\Voter\DataContainer;

use Contao\BackendUser;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;

class FrontendModulesVoter implements CacheableVoterInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === ContaoCorePermissions::DC_PREFIX.'tl_module';
    }

    public function supportsType(string $subjectType): bool
    {
        return \in_array($subjectType, [CreateAction::class, ReadAction::class, UpdateAction::class, DeleteAction::class], true);
    }

    public function vote(TokenInterface $token, mixed $subject, array $attributes)
    {
        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            $user = $this->security->getUser();

            if (!$user instanceof BackendUser) {
                return self::ACCESS_DENIED;
            }

            if ($user->isAdmin) {
                return self::ACCESS_GRANTED;
            }

            if (empty($user->frontendModules)) {
                return self::ACCESS_DENIED;
            }

            $isGranted = match (true) {
                $subject instanceof ReadAction => true,
                $subject instanceof CreateAction,
                $subject instanceof UpdateAction,
                $subject instanceof DeleteAction => $this->isAllowedType($subject, $user),
                default => false,
            };

            return $isGranted ? self::ACCESS_GRANTED : self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    private function isAllowedType(CreateAction|DeleteAction|ReadAction|UpdateAction $subject, BackendUser $user): bool
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
