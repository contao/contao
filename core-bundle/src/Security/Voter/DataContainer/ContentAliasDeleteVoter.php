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
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class ContentAliasDeleteVoter extends AbstractDataContainerVoter implements ResetInterface
{
    private array|null $cache = null;

    public function __construct(
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator,
    ) {
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

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action, Vote|null $vote = null): bool
    {
        if (!$action instanceof DeleteAction) {
            return true;
        }

        if (null === $this->cache) {
            $this->cache = $this->connection->fetchAllKeyValue("SELECT id, cteAlias FROM tl_content WHERE type = 'alias'");
        }

        $currentId = (int) $action->getCurrentId();

        if (false !== ($aliasId = array_search($currentId, $this->cache, true))) {
            $vote?->addReason($this->translator->trans('ERR.usedInAliasElement', [$currentId, $aliasId], 'contao_default'));

            return false;
        }

        return true;
    }
}
