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
use Contao\StringUtil;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCallback(table: 'tl_member', target: 'list.operations.su.button')]
class SwitchMemberOperationListener
{
    public function __construct(
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(DataContainerOperation $operation): void
    {
        $user = $this->security->getUser();
        $blnCanSwitchUser = $user instanceof BackendUser && ($user->isAdmin || (!empty($user->amg) && \is_array($user->amg)));

        if (!$blnCanSwitchUser) {
            $operation->hide();

            return;
        }

        $row = $operation->getRecord();

        if (
            !$row['login']
            || !$row['username']
            || (!$user->isAdmin && \count(array_intersect(StringUtil::deserialize($row['groups'], true), $user->amg)) < 1)
        ) {
            $operation->disable();

            return;
        }

        $operation['attributes']->set('data-turbo-prefetch', 'false')->set('target', '_blank');
        $operation->setUrl($this->urlGenerator->generate('contao_backend_preview', ['user' => $row['username']]));
    }
}
