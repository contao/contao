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

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Fragment\FragmentCompositor;
use Contao\DataContainer;
use Symfony\Component\Security\Core\Security;

/**
 * @internal
 */
class CteAllowedTypeListener
{
    public function __construct(
        private readonly FragmentCompositor $compositor,
        private readonly Security $security,
    ) {
    }

    /**
     * Switch to first allowed type if default type is not allowed (see #7100).
     */
    #[AsCallback(table: 'tl_content', target: 'config.oncreate')]
    public function setAllowedType(string $strTable, int $insertID, array $values, DataContainer $dc): void
    {
        $objCte = ContentModel::findById($insertID);
        $supportsNesting = 'tl_content' === $objCte->ptable && $this->compositor->supportsNesting('contao.content_element.'.$dc->getCurrentRecord()['type']);
        $allowedTypes = $supportsNesting ? $this->compositor->getAllowedTypes('contao.content_element.'.$dc->getCurrentRecord()['type']) : [];

        if ([] === $allowedTypes) {
            return;
        }

        $user = $this->security->getUser();

        if ($user->isAdmin) {
            if (!\in_array($objCte->type, $allowedTypes, true)) {
                $objCte->type = $allowedTypes[0];
                $objCte->save();
            }

            return;
        }

        $allowedTypesIntersect = array_intersect($allowedTypes, $user->elements);

        if ([] === $allowedTypesIntersect) {
            throw new AccessDeniedException('Not enough permissions to create allowed type  ('.implode(', ', $allowedTypes).').');
        }

        if (!\in_array($objCte->type, $allowedTypesIntersect, true)) {
            $objCte->type = $allowedTypesIntersect[0];
            $objCte->save();
        }
    }
}
