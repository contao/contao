<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Security\Voter;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDataContainerVoter;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

/**
 * @internal
 */
class NewsletterFailuresAccessVoter extends AbstractDataContainerVoter
{
    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly Connection $connection,
    ) {
    }

    protected function getTable(): string
    {
        return 'tl_newsletter_failures';
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        if (!$action instanceof ReadAction) {
            return false;
        }

        $newsletter = $this->connection->fetchAssociative('SELECT * FROM tl_newsletter WHERE id = ?', [$action->getCurrentPid()]);

        return $newsletter && $this->accessDecisionManager->decide($token, [ContaoCorePermissions::DC_PREFIX.'tl_newsletter'], new ReadAction('tl_newsletter', $newsletter));
    }
}
