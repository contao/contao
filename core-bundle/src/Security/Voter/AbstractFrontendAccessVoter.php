<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Voter;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendUser;
use Contao\Model;
use Contao\StringUtil;
use Contao\System;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class AbstractFrontendAccessVoter extends Voter implements ServiceSubscriberInterface
{
    public const ATTRIBUTE = 'contao_frontend.access';

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function setContainer(ContainerInterface $container): ?ContainerInterface
    {
        $previous = $this->container;
        $this->container = $container;

        return $previous;
    }

    public static function getSubscribedServices()
    {
        return [
            'contao.framework' => ContaoFramework::class,
        ];
    }

    protected function supports($attribute, $subject): bool
    {
        return self::ATTRIBUTE === $attribute && $this->supportsSubject($subject);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof FrontendUser || !\in_array('ROLE_MEMBER', $token->getRoleNames(), true)) {
            $user = null;
        }

        return $this->voteOnSubject($subject, $user);
    }

    abstract protected function supportsSubject($subject): bool;

    abstract protected function voteOnSubject($subject, ?FrontendUser $user): bool;

    protected function userHasGroups(?FrontendUser $user, $groups): bool
    {
        if (null === $user) {
            return false;
        }

        $groups = StringUtil::deserialize($groups);
        $userGroups = StringUtil::deserialize($user->groups);

        return !empty($groups)
            && \is_array($groups)
            && \is_array($userGroups)
            && \count(array_intersect($groups, $userGroups)) > 0;
    }

    protected function isVisibleElement(Model $model, bool $accessGranted)
    {
        $framework = $this->container->get('contao.framework');
        $framework->initialize();

        if (isset($GLOBALS['TL_HOOKS']['isVisibleElement']) && \is_array($GLOBALS['TL_HOOKS']['isVisibleElement'])) {
            @trigger_error('Using the "isVisibleElement" hook has been deprecated and will no longer work in Contao 5.0. Use Symfony security voters instead.', E_USER_DEPRECATED);

            $systemAdapter = $framework->getAdapter(System::class);

            foreach ($GLOBALS['TL_HOOKS']['isVisibleElement'] as $callback) {
                $accessGranted = $systemAdapter->importStatic($callback[0])->{$callback[1]}($model, $accessGranted);
            }
        }

        return $accessGranted;
    }
}
