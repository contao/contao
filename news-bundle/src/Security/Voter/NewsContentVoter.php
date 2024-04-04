<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Security\Voter;

use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDynamicPtableVoter;
use Contao\NewsBundle\Security\ContaoNewsPermissions;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

/**
 * @internal
 */
class NewsContentVoter extends AbstractDynamicPtableVoter
{
    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        Connection $connection,
    ) {
        parent::__construct($connection);
    }

    protected function getTable(): string
    {
        return 'tl_content';
    }

    protected function hasAccessToRecord(TokenInterface $token, UpdateAction|CreateAction|ReadAction|DeleteAction $action, array $record): bool
    {
        if ('tl_news' !== ($record['ptable'] ?? null) || !isset($record['pid'])) {
            return true;
        }

        return $this->accessDecisionManager->decide($token, [ContaoNewsPermissions::USER_CAN_ACCESS_MODULE])
            && $this->accessDecisionManager->decide($token, [ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE], $record['pid']);
    }
}
