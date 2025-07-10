<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\BackendUser;
use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;

#[AsCallback(table: 'tl_user', target: 'list.operations.su.button')]
class SwitchUserOperationListener
{
    public function __construct(
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(DataContainerOperation $operation): void
    {
        if (!$this->security->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            $operation->hide();

            return;
        }

        $row = $operation->getRecord();
        $user = $this->security->getUser();
        $token = $this->security->getToken();

        if (
            ($user instanceof BackendUser && (int) $user->id === (int) $row['id'])
            || (
                $token instanceof SwitchUserToken
                && ($origUser = $token->getOriginalToken()->getUser()) instanceof BackendUser
                && (int) $origUser->id === (int) $row['id']
            )
        ) {
            $operation->disable();

            return;
        }

        $operation['attributes']->set('data-turbo-prefetch', 'false');
        $operation->setUrl($this->urlGenerator->generate('contao_backend', ['_switch_user' => $row['username']]));
    }
}
