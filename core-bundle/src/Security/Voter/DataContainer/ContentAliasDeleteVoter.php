<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Voter\DataContainer;

use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
class ContentAliasDeleteVoter extends AbstractDataContainerVoter implements ResetInterface
{
    private array|null $cache = null;

    public function __construct(private readonly Connection $connection)
    {
    }

    public function reset(): void
    {
        $this->cache = null;
    }

    public function supportsType(string $subjectType): bool
    {
        return DeleteAction::class === $subjectType;
    }

    protected function getTable(): string
    {
        return 'tl_content';
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        if (!$action instanceof DeleteAction) {
            return true;
        }

        if (null === $this->cache) {
            $this->cache = $this->connection->fetchAllKeyValue("SELECT cteAlias, TRUE FROM tl_content WHERE type = 'alias' GROUP BY cteAlias");
        }

        return !isset($this->cache[(int) $action->getCurrentId()]);
    }
}
