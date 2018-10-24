<?php

namespace Contao\CoreBundle\Security\Voter;

use Contao\BackendUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BackendAccessVoter extends Voter
{
    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        return 0 === strpos($attribute, 'contao_user.');
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();
        [,$field] = explode('.', $attribute, 2);

        if (!$user instanceof BackendUser || (!\is_scalar($subject) && !\is_array($subject))) {
            return false;
        }

        return $user->hasAccess($subject, $field);
    }
}
