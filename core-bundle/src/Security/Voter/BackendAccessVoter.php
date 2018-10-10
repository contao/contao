<?php

namespace Contao\CoreBundle\Security\Voter;

use Contao\BackendUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class BackendAccessVoter extends Voter
{
    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        return 0 === strpos($attribute, 'contao_user.') && (\is_scalar($subject) || \is_array($subject));
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();
        [$table, $field] = explode('.', $attribute, 2);

        if (!$user instanceof BackendUser || 'contao_user' !== $table) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        return $user->hasAccess($subject, $field) ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED;
    }
}
