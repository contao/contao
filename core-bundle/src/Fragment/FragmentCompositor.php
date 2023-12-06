<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fragment;

use Contao\ContentModel;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\Model\Registry;

class FragmentCompositor
{
    private array $nestedByIdentifier = [];

    /**
     * @internal Do not inherit from this class; decorate the "contao.fragment.compositor" service instead
     */
    public function __construct()
    {
    }

    public function add(string $identifier, array $nestedElements): void
    {
        $this->nestedByIdentifier[$identifier] = $nestedElements;
    }

    public function isNested(string $identifier): bool
    {
        return isset($this->nestedByIdentifier[$identifier]);
    }

    public function getNestedContentElements(string $identifier, int $id): array
    {
        if (!isset($this->nestedByIdentifier[$identifier])) {
            return [];
        }

        $rendered = [];
        $children = ContentModel::findPublishedByPidAndTable($id, ContentModel::getTable());
        $allowedTypes = $this->nestedByIdentifier[$identifier]['allowedTypes'] ?? [];

        foreach ($children ?? [] as $child) {
            if ([] !== $allowedTypes && !\in_array($child->type, $allowedTypes, true)) {
                continue;
            }

            $rendered[] = new ContentElementReference(
                $child,
                'main',
                [],
                !Registry::getInstance()->isRegistered($child),
                $this->getNestedContentElements(ContentElementReference::TAG_NAME.'.'.$child->type, $child->id),
            );
        }

        return $rendered;
    }
}
