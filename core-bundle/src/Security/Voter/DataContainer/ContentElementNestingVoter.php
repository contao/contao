<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Security\Voter\DataContainer;

use Contao\CoreBundle\Fragment\FragmentCompositor;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Checks if a content element can be nested within another. This does not check
 * access to the parent table (e.g. articles)!
 *
 * @internal
 */
class ContentElementNestingVoter extends AbstractDataContainerVoter implements ResetInterface
{
    private array $types = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly FragmentCompositor $compositor,
    ) {
    }

    public function reset(): void
    {
        $this->types = [];
    }

    protected function getTable(): string
    {
        return 'tl_content';
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        // Check access to list children of an element (children operation).
        if (
            $action instanceof ReadAction
            && !isset($action->getCurrent()['type'])
            && 'tl_content' === ($action->getCurrent()['ptable'] ?? null)
            && ($pid = (int) $action->getCurrentPid()) > 0
            && !$this->canNestInParent($pid, '')
        ) {
            return false;
        }

        // Never disallow to read or delete invalid nested elements here. (Delete) access
        // to an element type is checked by other voters.
        if ($action instanceof ReadAction || $action instanceof DeleteAction) {
            return true;
        }

        $type = $action->getNew()['type'] ?? ($action instanceof UpdateAction ? $action->getCurrent()['type'] ?? '' : '');
        $ptable = $action->getNew()['ptable'] ?? ($action instanceof UpdateAction ? $action->getCurrent()['ptable'] ?? null : null);

        // Check access if element is moved to or created in a new ptable
        return !('tl_content' === $ptable
            && ($pid = (int) $action->getNewPid()) > 0
            && !$this->canNestInParent($pid, $type));
    }

    private function canNestInParent(int $pid, string $type): bool
    {
        if (isset($this->types[$pid])) {
            if (\is_bool($this->types[$pid])) {
                return $this->types[$pid];
            }

            return '' === $type || \in_array($type, $this->types[$pid], true);
        }

        $parentType = $this->connection->fetchOne('SELECT type FROM tl_content WHERE id=?', [$pid]);

        if (
            false === $parentType
            || !$this->compositor->supportsNesting(ContentElementReference::TAG_NAME.'.'.$parentType)
        ) {
            return $this->types[$pid] = false;
        }

        $this->types[$pid] = $this->compositor->getAllowedTypes('contao.content_element.'.$parentType);

        if ([] === $this->types[$pid]) {
            return $this->types[$pid] = true;
        }

        // Check this after assigning $this->types to cache the database query
        if ('' === $type) {
            return true;
        }

        return \in_array($type, $this->types[$pid], true);
    }
}
